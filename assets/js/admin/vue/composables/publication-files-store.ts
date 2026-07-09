import { reactive, readonly } from 'vue';

const state = reactive({
  hasMainDocument: false,
  hasNotice: false,
});

export const usePublicationFilesStore = () => ({
  state: readonly(state),
  setHasMainDocument: (value: boolean) => {
    state.hasMainDocument = value;
  },
  setHasNotice: (value: boolean) => {
    state.hasNotice = value;
  },
});
