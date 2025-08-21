<script setup lang="ts">
import Dialog from '@admin-fe/component/Dialog.vue';
import Icon from '@admin-fe/component/Icon.vue';
import { getHelpId } from '@admin-fe/form';
import { md } from './markdownit';
import { ref, useTemplateRef, nextTick } from 'vue';

interface Props {
  id: string;
  name: string;
  value: string;
}

const props = defineProps<Props>();
const markdown = ref(props.value?.replace(/\r/g, '') ?? '');
const textarea = useTemplateRef<HTMLTextAreaElement>('textarea');
const isDialogOpen = ref(false);

const NUMBERED_LIST_PATTERN = /^\d+\.\s/;
const BULLET_LIST_PATTERN = /^[-*]\s/;

const getWordBoundaries = (text: string, position: number) => {
  let start = position;
  while (start > 0 && !/\s/.test(text[start - 1])) {
    start--;
  }

  let end = position;
  while (end < text.length && !/\s/.test(text[end])) {
    end++;
  }

  return { start, end };
};

const calculateNewSelection = (
  isTextSelected: boolean,
  startPosition: number,
  endPosition: number,
  cursorPosition: number,
  tokenLength: number,
  isRemovingTokens: boolean,
) => {
  if (isTextSelected) {
    return {
      start: startPosition + (isRemovingTokens ? -tokenLength : tokenLength),
      end: endPosition + (isRemovingTokens ? -tokenLength : tokenLength),
    };
  }

  const relativePosition = cursorPosition - startPosition;
  return {
    start:
      startPosition +
      relativePosition +
      (isRemovingTokens ? -tokenLength : tokenLength),
    end:
      startPosition +
      relativePosition +
      (isRemovingTokens ? -tokenLength : tokenLength),
  };
};

const getNewSelectionInfo = (
  token: string,
  isTextSelected: boolean,
  startPosition: number,
  endPosition: number,
  cursorPosition: number,
  isSurroundedByTokens: boolean,
  startsAndEndsWithTokens: boolean,
) => {
  if (isSurroundedByTokens) {
    return calculateNewSelection(
      isTextSelected,
      startPosition,
      endPosition,
      cursorPosition,
      token.length,
      true,
    );
  }

  if (startsAndEndsWithTokens) {
    return getSelectionInfoAfterTrimmingTokens(
      token,
      isTextSelected,
      startPosition,
      endPosition,
      cursorPosition,
    );
  }

  return calculateNewSelection(
    isTextSelected,
    startPosition,
    endPosition,
    cursorPosition,
    token.length,
    false,
  );
};

const getSelectionInfoAfterTrimmingTokens = (
  token: string,
  isTextSelected: boolean,
  startPosition: number,
  endPosition: number,
  cursorPosition: number,
) => {
  if (isTextSelected) {
    return {
      start: startPosition,
      end: endPosition - token.length * 2,
    };
  }

  return calculateNewSelection(
    isTextSelected,
    startPosition,
    endPosition,
    cursorPosition,
    token.length,
    true,
  );
};

const toggleToken = (token: string) => {
  if (!textarea.value) {
    return;
  }

  const isTextSelected =
    textarea.value.selectionStart !== textarea.value.selectionEnd;
  const text = markdown.value;

  const { start, end } = isTextSelected
    ? {
        start: textarea.value.selectionStart,
        end: textarea.value.selectionEnd,
      }
    : getWordBoundaries(text, textarea.value.selectionStart);

  const textBefore = text.substring(0, start);
  const textSelected = text.substring(start, end);
  const textAfter = text.substring(end);

  const isSurroundedByTokens =
    text.substring(Math.max(0, start - token.length), start) === token &&
    text.substring(end, Math.min(text.length, end + token.length)) === token;

  const startsAndEndsWithTokens =
    textSelected.startsWith(token) && textSelected.endsWith(token);

  const { start: newStart, end: newEnd } = getNewSelectionInfo(
    token,
    isTextSelected,
    start,
    end,
    textarea.value.selectionStart,
    isSurroundedByTokens,
    startsAndEndsWithTokens,
  );

  markdown.value = createNewMarkdown(
    textBefore,
    textSelected,
    textAfter,
    token,
    isSurroundedByTokens,
    startsAndEndsWithTokens,
  );
  updateTextareaSelection(textarea.value, newStart, newEnd);
};

const createNewMarkdown = (
  textBefore: string,
  textSelected: string,
  textAfter: string,
  token: string,
  isSurroundedByTokens: boolean,
  startsAndEndsWithTokens: boolean,
) => {
  if (isSurroundedByTokens) {
    return (
      textBefore.slice(0, -token.length) +
      textSelected +
      textAfter.slice(token.length)
    );
  }

  if (startsAndEndsWithTokens) {
    return (
      textBefore + textSelected.slice(token.length, -token.length) + textAfter
    );
  }

  return textBefore + token + textSelected + token + textAfter;
};

const getListPattern = (isNumberedList: boolean): RegExp =>
  isNumberedList ? NUMBERED_LIST_PATTERN : BULLET_LIST_PATTERN;

const getListPrefix = (isNumberedList: boolean, number?: number): string =>
  isNumberedList ? `${number ?? 1}. ` : '- ';

const getListOffset = (isNumberedList: boolean, hasList: boolean): number => {
  if (hasList) {
    return isNumberedList ? -3 : -2;
  }

  return isNumberedList ? 3 : 2;
};

const processSingleLine = (
  line: string,
  hasList: boolean,
  isNumberedList: boolean,
  number?: number,
): { newLine: string; offset: number } => {
  if (hasList) {
    return {
      newLine: line.replace(getListPattern(isNumberedList), ''),
      offset: getListOffset(isNumberedList, true),
    };
  }

  return {
    newLine: getListPrefix(isNumberedList, number) + line,
    offset: getListOffset(isNumberedList, false),
  };
};

const processSelectedLines = (
  selectedText: string,
  hasList: boolean,
  isNumberedList: boolean,
): { newText: string; offset: number } => {
  const lines = selectedText.split('\n');
  let currentNumber = 1;

  const processedLines = lines.map((line) => {
    const result = processSingleLine(
      line,
      hasList,
      isNumberedList,
      isNumberedList ? currentNumber : undefined,
    );
    if (isNumberedList && !hasList) {
      currentNumber++;
    }
    return result.newLine;
  });

  return {
    newText: processedLines.join('\n'),
    offset: getListOffset(isNumberedList, hasList),
  };
};

const processSelectedText = (
  textarea: HTMLTextAreaElement,
  isNumberedList: boolean,
) => {
  const selectedText = markdown.value.substring(
    textarea.selectionStart,
    textarea.selectionEnd,
  );

  const lines = selectedText.split('\n');
  let startIndex = 0;
  while (startIndex < lines.length && lines[startIndex].trim() === '') {
    startIndex++;
  }
  const relevantLines = lines.slice(startIndex);

  const hasList = relevantLines.every((line) =>
    getListPattern(isNumberedList).test(line),
  );

  const { newText, offset } = processSelectedLines(
    relevantLines.join('\n'),
    hasList,
    isNumberedList,
  );

  const textBeforeSelection = markdown.value.substring(
    0,
    textarea.selectionStart,
  );
  const lastNewlineBeforeSelection = textBeforeSelection.lastIndexOf('\n');
  const isFirstLine = lastNewlineBeforeSelection === -1;
  const hasEmptyLineBefore =
    !isFirstLine &&
    textBeforeSelection[lastNewlineBeforeSelection - 1] === '\n';

  const finalText =
    lines.slice(0, startIndex).join('\n') +
    (startIndex > 0 ? '\n' : '') +
    (isFirstLine || hasEmptyLineBefore ? '' : '\n') +
    newText;

  const newSelectionStart =
    textarea.selectionStart + (isFirstLine || hasEmptyLineBefore ? 0 : 1);
  const newSelectionEnd =
    textarea.selectionEnd +
    offset * relevantLines.length +
    (isFirstLine || hasEmptyLineBefore ? 0 : 1);

  markdown.value =
    markdown.value.substring(0, textarea.selectionStart) +
    finalText +
    markdown.value.substring(textarea.selectionEnd);

  updateTextareaSelection(textarea, newSelectionStart, newSelectionEnd);
};

const processCurrentLine = (
  textarea: HTMLTextAreaElement,
  isNumberedList: boolean,
) => {
  const cursorPosition = textarea.selectionStart;
  const textBeforeCursor = markdown.value.substring(0, cursorPosition);
  const lastNewline = textBeforeCursor.lastIndexOf('\n');
  const currentLineStart = lastNewline + 1;
  const currentLineText = markdown.value.substring(
    currentLineStart,
    cursorPosition,
  );
  const hasList = getListPattern(isNumberedList).test(currentLineText);

  const { newLine, offset } = processSingleLine(
    currentLineText,
    hasList,
    isNumberedList,
    isNumberedList ? 1 : undefined,
  );

  const isFirstLine = lastNewline === -1;
  const hasEmptyLineBefore =
    !isFirstLine && textBeforeCursor[lastNewline - 1] === '\n';

  const newText = hasList
    ? markdown.value.substring(0, currentLineStart) +
      newLine +
      markdown.value.substring(cursorPosition)
    : markdown.value.substring(0, currentLineStart) +
      (isFirstLine || hasEmptyLineBefore ? '' : '\n') +
      newLine +
      markdown.value.substring(cursorPosition);

  const newCursorPosition =
    cursorPosition +
    offset +
    (hasList || isFirstLine || hasEmptyLineBefore ? 0 : 1);

  markdown.value = newText;
  updateTextareaSelection(textarea, newCursorPosition, newCursorPosition);
};

const toggleList = (isNumberedList: boolean) => {
  if (!textarea.value) {
    return;
  }

  const isTextSelected =
    textarea.value.selectionStart !== textarea.value.selectionEnd;

  if (isTextSelected) {
    processSelectedText(textarea.value, isNumberedList);
  } else {
    processCurrentLine(textarea.value, isNumberedList);
  }
};

const updateTextareaSelection = async (
  textarea: HTMLTextAreaElement,
  newSelectionStart: number,
  newSelectionEnd: number,
) => {
  await nextTick();
  textarea.setSelectionRange(newSelectionStart, newSelectionEnd);
  textarea.focus();
};

const onBold = () => {
  toggleToken('**');
};

const onItalic = () => {
  toggleToken('_');
};

const onLink = () => {
  if (!textarea.value) {
    return;
  }

  const isTextSelected =
    textarea.value.selectionStart !== textarea.value.selectionEnd;
  const { start, end } = isTextSelected
    ? {
        start: textarea.value.selectionStart,
        end: textarea.value.selectionEnd,
      }
    : getWordBoundaries(markdown.value, textarea.value.selectionStart);

  const selectedText = markdown.value.substring(start, end);

  const newText =
    markdown.value.substring(0, start) +
    `[${selectedText}](url)` +
    markdown.value.substring(end);

  markdown.value = newText;

  const urlStartPosition = start + selectedText.length + 3;
  const urlEndPosition = urlStartPosition + 3;
  updateTextareaSelection(textarea.value, urlStartPosition, urlEndPosition);
};

const onNumberList = () => {
  toggleList(true);
};

const onBulletList = () => {
  toggleList(false);
};
</script>

<template>
  <div class="flex justify-between">
    <div class="flex">
      <button class="toolbar-button" type="button" @click="onBold">
        <Icon name="format-bold" color="fill-current" :size="24" />
        <span class="sr-only">Tekst dikgedrukt of niet-dikgedrukt maken</span>
      </button>
      <button class="toolbar-button" type="button" @click="onItalic">
        <Icon name="format-italic" color="fill-current" :size="24" />
        <span class="sr-only">Tekst cursief of niet-cursief maken</span>
      </button>
      <button class="toolbar-button" type="button" @click="onLink">
        <Icon name="format-link" color="fill-current" :size="24" />
        <span class="sr-only">Link toevoegen</span>
      </button>
      <button class="toolbar-button" type="button" @click="onBulletList">
        <Icon name="format-list-bullet" color="fill-current" :size="24" />
        <span class="sr-only">Ongenummerde lijst toevoegen of weghalen</span>
      </button>
      <button class="toolbar-button" type="button" @click="onNumberList">
        <Icon name="format-list-number" color="fill-current" :size="24" />
        <span class="sr-only">Genummerde lijst toevoegen of weghalen</span>
      </button>
    </div>

    <button
      @click="isDialogOpen = true"
      aria-haspopup="dialog"
      class="bhr-btn-ghost-primary"
      type="button"
    >
      Wat is Markdown en hoe werkt het?
    </button>
  </div>

  <textarea
    :aria-describedby="getHelpId(props.id)"
    :id="props.id"
    :name="props.name"
    class="bhr-textarea min-h-60"
    ref="textarea"
    v-model="markdown"
  />

  <h2 class="bhr-label mt-4">Preview</h2>
  <div class="preview bhr-content" v-html="md.render(markdown)" />

  <Dialog v-model="isDialogOpen" title="Wat is markdown en hoe werkt het?">
    <p>
      Markdown is een opmaaktaal waarmee je tekst in verschillende stijlen kunt
      opmaken. Hiervoor gebruik je leestekens zoals sterretjes of streepjes.
    </p>

    <table class="bhr-table my-6">
      <thead>
        <tr>
          <th class="bhr-column-head bhr-column-head--gray">Tekst</th>
          <th class="bhr-column-head bhr-column-head--gray">Resultaat</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>**Dikgedrukt**</td>
          <td>
            <span class="bhr-bold">Dikgedrukt</span>
          </td>
        </tr>
        <tr>
          <td>_Cursief_</td>
          <td>
            <span class="bhr-italic">Cursief</span>
          </td>
        </tr>
        <tr>
          <td>[Tekst van link](https://www.example.nl)</td>
          <td>
            <a class="bhr-a" href="https://www.example.nl" target="_blank">
              Tekst van link
            </a>
          </td>
        </tr>
        <tr>
          <td>
            - Eerste item
            <br />
            - Tweede item
          </td>
          <td>
            <ul class="bhr-ul">
              <li class="bhr-li">Eerste item</li>
              <li class="bhr-li">Tweede item</li>
            </ul>
          </td>
        </tr>
        <tr>
          <td>
            1. Eerste item
            <br />
            2. Tweede item
          </td>
          <td>
            <ol class="bhr-ol">
              <li class="bhr-li">Eerste item</li>
              <li class="bhr-li">Tweede item</li>
            </ol>
          </td>
        </tr>
      </tbody>
    </table>

    <button
      @click="isDialogOpen = false"
      class="bhr-btn-filled-primary"
      type="button"
    >
      Sluit dit venster
    </button>
  </Dialog>
</template>

<style lang="postcss" scoped>
@reference '../../../../../../styles/admin/index.css';

.toolbar-button {
  @apply bhr-btn-ghost-primary w-10 aspect-square;
}

.preview {
  @apply p-2 pl-4 text-lg;

  &:not(:empty) {
    @apply border-l border-bhr-gray-500;
  }
}
</style>
