export const beforeUnload = (fn) => {
    window.addEventListener('beforeunload', fn);
}
