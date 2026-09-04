/**
 * Lightweight WYSIWYG for news announcements (UR-NEW-03).
 * Works with classic forms (hidden body_html) and optional Livewire entanglement.
 */
(function () {
    document.addEventListener('alpine:init', function () {
        Alpine.data('ojRichEditor', function () {
            return {
                html: '',
                uploadingInline: false,
                init: function () {
                    var seed = this.html && String(this.html).trim() !== '' ? this.html : '<p></p>';
                    if (this.$refs.editor) {
                        this.$refs.editor.innerHTML = seed;
                    }

                    this.$watch('html', (value) => {
                        if (!this.$refs.editor || document.activeElement === this.$refs.editor) {
                            return;
                        }
                        var next = value && String(value).trim() !== '' ? value : '<p></p>';
                        if (this.$refs.editor.innerHTML !== next) {
                            this.$refs.editor.innerHTML = next;
                        }
                    });
                },
                focusEditor: function () {
                    if (this.$refs.editor) {
                        this.$refs.editor.focus();
                    }
                },
                cmd: function (command, value) {
                    this.focusEditor();
                    document.execCommand(command, false, value == null ? null : value);
                    this.sync();
                },
                link: function () {
                    var url = window.prompt('Link URL', 'https://');
                    if (!url) {
                        return;
                    }
                    this.cmd('createLink', url);
                },
                insertImage: function (url) {
                    if (!url || typeof url !== 'string') {
                        return;
                    }
                    this.focusEditor();
                    document.execCommand(
                        'insertHTML',
                        false,
                        '<p><img src="' + this.escapeAttr(url) + '" alt=""></p>'
                    );
                    this.sync();
                },
                uploadInline: function (event) {
                    var input = event.target;
                    var file = input && input.files && input.files[0];
                    if (!file) {
                        return;
                    }

                    var form = this.$el.closest('form') || this.$el;
                    var uploadUrl = (form && form.getAttribute('data-inline-upload')) || '';
                    if (!uploadUrl) {
                        window.alert('Inline image upload is not configured.');
                        input.value = '';
                        return;
                    }

                    var tokenInput = form.querySelector('input[name="_token"]');
                    var token = tokenInput ? tokenInput.value : '';
                    var body = new FormData();
                    body.append('image', file);
                    if (token) {
                        body.append('_token', token);
                    }

                    this.uploadingInline = true;
                    var self = this;
                    fetch(uploadUrl, {
                        method: 'POST',
                        body: body,
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    })
                        .then(function (res) {
                            return res.json().then(function (data) {
                                if (!res.ok) {
                                    throw new Error((data && data.message) || 'Image upload failed.');
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            if (data && data.url) {
                                self.insertImage(data.url);
                            }
                        })
                        .catch(function (err) {
                            window.alert(err && err.message ? err.message : 'Image upload failed.');
                        })
                        .finally(function () {
                            self.uploadingInline = false;
                            input.value = '';
                        });
                },
                sync: function () {
                    if (!this.$refs.editor) {
                        return;
                    }
                    var raw = this.$refs.editor.innerHTML;
                    var plain = this.$refs.editor.innerText.replace(/\u00a0/g, ' ').trim();
                    this.html = plain === '' ? '' : raw;
                },
                escapeAttr: function (value) {
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                },
            };
        });
    });
})();
