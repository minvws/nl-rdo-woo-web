export const onKeyDown = (keyName, fn, options) => {
    window.addEventListener('keydown', (event) => {
        if (event.key !== keyName) {
            return;
        }

        fn();
    }, options);
}
