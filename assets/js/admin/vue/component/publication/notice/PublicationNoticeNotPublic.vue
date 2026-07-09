<script setup lang="ts">
import Alert from '@admin-fe/component/Alert.vue';
import Dialog from '@admin-fe/component/Dialog.vue';
import Icon from '@admin-fe/component/Icon.vue';
import { usePublicationFilesStore } from '@admin-fe/composables';
import { isSuccessStatusCode, validateResponse } from '@js/admin/utils';
import { format } from 'date-fns';
import { nl } from 'date-fns/locale';
import { computed, ref } from 'vue';
import { noticeSchema, type GroundOptions, type Notice } from './interface';
import PublicationNoticeNotPublicForm from './PublicationNoticeNotPublicForm.vue';

interface Props {
  canDelete: boolean;
  endpoint: string;
  groundOptions: GroundOptions;
  hasMainDocument: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  canDelete: false,
  hasMainDocument: false,
});

const store = usePublicationFilesStore();
store.setHasMainDocument(props.hasMainDocument);

const notice = ref<Notice | null>(null);
const isDialogOpen = ref(false);
const isEditMode = ref(false);
const updatedNotice = ref<{
  action: 'created' | 'deleted' | 'updated' | null;
  notice: Notice | null;
}>({ action: null, notice: null });
const hasDeleteError = ref(false);

const hasNotice = computed(() => notice.value !== null);
const canAddNotice = computed(() => !store.state.hasMainDocument);
const isAddButtonDisabled = computed(() => !canAddNotice.value);
const groundCount = computed(() => notice.value?.grounds.length || 0);
const hasNoticeExplanation = computed(() => Boolean(notice.value?.explanation));

const resetUpdatedNotice = () => {
  updatedNotice.value = { action: null, notice: null };
};

const onCancel = () => {
  isDialogOpen.value = false;
};

const onEdit = () => {
  resetUpdatedNotice();
  isEditMode.value = true;
  isDialogOpen.value = true;
};

const onAddNotice = () => {
  resetUpdatedNotice();
  isEditMode.value = false;
  isDialogOpen.value = true;
};

const onSaved = (savedNotice: Notice) => {
  updatedNotice.value = {
    notice: savedNotice,
    action: isEditMode.value ? 'updated' : 'created',
  };
  notice.value = savedNotice;
  store.setHasNotice(true);
  isDialogOpen.value = false;
};

const onDelete = async () => {
  try {
    const response = await fetch(props.endpoint, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        accept: 'application/json',
      },
    });

    if (!isSuccessStatusCode(response.status)) {
      hasDeleteError.value = true;
      return;
    }

    const deletedNotice = { ...notice.value! };
    notice.value = null;
    store.setHasNotice(false);
    updatedNotice.value = {
      action: 'deleted',
      notice: deletedNotice,
    };
  } catch {
    hasDeleteError.value = true;
  }
};

const retrieveNotice = async () => {
  try {
    const request = fetch(props.endpoint, {
      headers: {
        'Content-Type': 'application/json',
        accept: 'application/json',
      },
    });
    const noticeData = await validateResponse(request, noticeSchema);
    notice.value = noticeData;
    store.setHasNotice(notice.value !== null);
  } catch {
    // no notice exists yet
  }
};

retrieveNotice();
</script>

<template>
  <div>
    <div class="pb-2" data-e2e-name="alerts" v-if="updatedNotice.notice">
      <Alert type="success">
        Mededeling is
        {{
          updatedNotice.action === 'deleted'
            ? 'verwijderd'
            : updatedNotice.action === 'created'
              ? 'toegevoegd'
              : 'bijgewerkt'
        }}.
      </Alert>
    </div>

    <div v-if="hasNotice" data-e2e-name="notice-preview">
      <div class="bhr-file mt-2">
        <div class="bhr-file__left">
          <div class="bhr-file__info-area">
            <div>
              {{ notice?.documentName || 'Documentnaam niet opgegeven' }}
              <time class="ml-1" :datetime="notice?.formalDate"
                >({{
                  format(notice?.formalDate ?? '', 'd MMMM yyyy', {
                    locale: nl,
                  })
                }})</time
              >
            </div>
            <span class="text-sm text-bhr-gray-700">
              {{ groundCount }} weigeringsgrond{{
                groundCount !== 1 ? 'en' : ''
              }}
              en {{ hasNoticeExplanation ? 'een' : 'geen' }} toelichting
            </span>
          </div>
        </div>
        <div class="flex">
          <button
            @click="onEdit"
            aria-haspopup="dialog"
            class="bhr-a"
            data-e2e-name="edit-notice"
            type="button"
          >
            Aanpassen
          </button>
          <button
            v-if="props.canDelete"
            @click="onDelete"
            class="bhr-btn-ghost-danger w-12"
            data-e2e-name="delete-notice"
            type="button"
          >
            <Icon color="fill-current" :size="20" name="trash-bin" />
            <span class="sr-only">Verwijder mededeling</span>
          </button>
        </div>
      </div>
    </div>

    <button
      v-else
      @click="onAddNotice"
      aria-haspopup="dialog"
      :class="{ 'opacity-50 cursor-not-allowed': isAddButtonDisabled }"
      class="bhr-btn-ghost-primary mt-1"
      :disabled="isAddButtonDisabled"
      data-e2e-name="add-notice"
      type="button"
    >
      <Icon
        class="bhr-btn__icon-left"
        color="fill-current"
        name="plus"
        :size="24"
      />
      Mededeling toevoegen...
    </button>

    <div v-if="hasDeleteError" class="pb-2 mt-2" data-e2e-name="delete-failed">
      <Alert type="warning">
        Het verwijderen van de mededeling is mislukt. Probeer het later opnieuw.
      </Alert>
    </div>
  </div>

  <Teleport to="body">
    <Dialog
      v-model="isDialogOpen"
      :e2e-name="'notice-not-public'"
      :title="isEditMode ? 'Mededeling bewerken' : 'Mededeling toevoegen'"
    >
      <PublicationNoticeNotPublicForm
        @cancel="onCancel"
        @saved="onSaved"
        :endpoint="props.endpoint"
        :ground-options="props.groundOptions"
        :is-edit-mode="isEditMode"
        :notice="notice"
      />
    </Dialog>
  </Teleport>
</template>
