<script>
(function () {
    function initGlSelectors() {
        document.querySelectorAll('.gl-search-input').forEach(function (searchEl) {
            if (searchEl.dataset.glInit) return; // prevent double-init
            searchEl.dataset.glInit = '1';

            var uid        = searchEl.dataset.uid;
            var dropdown   = document.getElementById('gl_dropdown_' + uid);
            var hiddenEl   = document.getElementById('gl_hidden_' + uid);
            var noResults  = dropdown ? dropdown.querySelector('.gl-no-results') : null;

            if (!dropdown || !hiddenEl) return;

            var options = dropdown.querySelectorAll('.gl-option');

            // Show dropdown on focus
            searchEl.addEventListener('focus', function () {
                filterOptions('');
                dropdown.classList.remove('hidden');
            });

            // Filter on input
            searchEl.addEventListener('input', function () {
                filterOptions(this.value.toLowerCase());
                dropdown.classList.remove('hidden');
            });

            // Select option
            options.forEach(function (opt) {
                opt.addEventListener('click', function () {
                    hiddenEl.value  = this.dataset.code;
                    searchEl.value  = this.dataset.code + ' — ' + this.dataset.name;
                    dropdown.classList.add('hidden');
                });
            });

            // Close on outside click
            document.addEventListener('click', function (e) {
                if (!searchEl.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    // If search text doesn't match hidden value, restore label
                    if (!hiddenEl.value && searchEl.value) {
                        // partial text entered but nothing selected — clear
                        // keep as-is so user can see what they typed
                    }
                }
            });

            function filterOptions(q) {
                var visible = 0;
                options.forEach(function (opt) {
                    var match = !q || opt.dataset.search.includes(q);
                    opt.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (noResults) noResults.classList.toggle('hidden', visible > 0);
            }
        });
    }

    // Init on DOM ready and also expose for dynamic forms
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlSelectors);
    } else {
        initGlSelectors();
    }

    window.initGlSelectors = initGlSelectors;

    /**
     * Programmatically set a GL selector's value by account code.
     * Usage: window.setGlSelectorValue('fa_gl_account', '121100006');
     */
    window.setGlSelectorValue = function (uid, code) {
        var hiddenEl  = document.getElementById('gl_hidden_' + uid);
        var searchEl  = document.querySelector('.gl-search-input[data-uid="' + uid + '"]');
        var dropdown  = document.getElementById('gl_dropdown_' + uid);
        if (!hiddenEl || !searchEl || !dropdown) return;

        var found = false;
        dropdown.querySelectorAll('.gl-option').forEach(function (opt) {
            if (opt.dataset.code === code) {
                hiddenEl.value = code;
                searchEl.value = code + ' — ' + opt.dataset.name;
                found = true;
            }
        });
        if (!found) {
            hiddenEl.value = code;
            searchEl.value = code;
        }
    };
})();
</script>
