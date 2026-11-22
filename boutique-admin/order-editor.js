(() => {
    const frame = document.getElementById('ib-order-editor');
    const fallback = document.getElementById('ib-order-editor-fallback');
    const stylesheet = document.getElementById('ib-styles');

    function fitEditor() {
        fallback.hidden = false;

        try {
            const editor = frame.contentDocument;
            const content = editor?.getElementById('wpbody-content');
            if (!content || !editor.getElementById('woocommerce-order-data')) {
                return;
            }

            fallback.hidden = true;
            editor.documentElement.classList.add('ib-embedded-order');
            if (!editor.getElementById('ib-embedded-styles')) {
                const link = editor.createElement('link');
                link.id = 'ib-embedded-styles';
                link.rel = 'stylesheet';
                link.href = stylesheet.href;
                editor.head.append(link);
            }
        } catch (error) {
            if (error.name !== 'SecurityError') throw error;
        }
    }

    frame.addEventListener('load', fitEditor);
    fitEditor();
})();
