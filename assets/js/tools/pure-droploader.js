(function (pure) {
    'use strict'

    function Droploader(area) {
        this.area = area;
        this.input = area.querySelector('.pure-droploader');
        this.input = this.input || area.querySelector('input[type=file]');
        this.init();
    }

    Droploader.prototype.init = function () {
        if (pureDroploader) {
            this.dispatch('pureDroploaderInit', { droploader: this });

            ['dragenter', 'dragover'].forEach(function (name) {
                this.area.addEventListener(name, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.area.classList.add('hover');
                }.bind(this), false);
            }, this);

            ['dragleave', 'drop'].forEach(function (name) {
                this.area.addEventListener(name, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.area.classList.remove('hover');
                }.bind(this), false);
            }, this);

            this.area.addEventListener('drop', function (event) {
                this.files(event.dataTransfer.files);
            }.bind(this), false);

            this.input.addEventListener('change', function (event) {
                this.files(event.target.files);
            }.bind(this), false);

            this.dispatch('pureDroploaderReady', { droploader: this });
        }
    }

    Droploader.prototype.files = function (files) {
        this.dispatch('pureDroploaderFiles', {
            droploader: this,
            files: files
        });

        for (let i = 0, len = files.length; i < len; i++) {
            this.upload(files[i]);
            this.preview(files[i]);
        }
    }

    Droploader.prototype.upload = function (file) {
        let formdata = new FormData();
        formdata.append('file', file);

        this.dispatch('pureDroploaderUpload', {
            droploader: this,
            file: file,
            formdata: formdata
        });

        this.wpRest(formdata, function (status, response) {
            this.dispatch('pureDroploaderUploaded', {
                droploader: this,
                file: file,
                status: status,
                response: response
            });
        });
    }

    Droploader.prototype.preview = function (file) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function () {

            let picture = this.area.querySelector('picture.preview');
            let image = this.area.querySelector('img.preview');

            if (picture) {
                image = picture.querySelector('img');
                image.classList.add('preview');
                picture.parentNode.insertBefore(image, picture);
                picture.parentNode.removeChild(picture);
                picture = null;
            }

            if (!image) {
                image = document.createElement('img');
                image.classList.add('preview');
                this.area.insertBefore(image, this.area.firstChild || null);
            }

            this.dispatch('pureDroploaderPreview', {
                droploader: this,
                file: file,
                reader: reader,
                image: image
            });

            image.src = reader.result;
        }.bind(this);
    }

    Droploader.prototype.dispatch = function (type, detail) {
        this.area.dispatchEvent(new CustomEvent(type, {
            bubbles: true,
            detail: detail,
        }));
    }

    Droploader.prototype.wpAjax = function (action, formdata, callback) {
        const xhr = new XMLHttpRequest();
        formdata.append('action', action);
        xhr.open('POST', pureDroploader.wpAjax || '/wp-admin/admin-ajax.php');
        xhr.send(formdata);
        xhr.onload = function () {
            callback.call(this, xhr.status, xhr.responseText);
        }.bind(this);
    }

    Droploader.prototype.wpRest = function (formdata, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', pureDroploader.wpRest || '/wp-json/wp/v2/media/');
        xhr.setRequestHeader('X-WP-Nonce', pureDroploader.wpNonce);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formdata);
        xhr.onload = function () {
            callback.call(this, xhr.status, xhr.responseText);
        }.bind(this);
    }

    /* ===== Static methods ========================= */

    Object.defineProperty(Droploader, 'auto', {
        configurable: true,
        value: function () {
            document.querySelectorAll('.pure-droploader-area').forEach(function (area) {
                new Droploader(area);
            });
        }
    });

    Object.defineProperty(Droploader, 'ready', {
        configurable: true,
        value: function (callback) {
            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', callback) : callback();
        },
    });

    /* ===== Polyfills ========================= */

    if (typeof (window.CustomEvent) !== "function") {
        window.CustomEvent = function (type, params) {
            params = params || { bubbles: false, cancelable: false, detail: null };
            var event = document.createEvent('CustomEvent');
            event.initCustomEvent(type, params.bubbles, params.cancelable, params.detail);
            return event;
        }
    }

    if (window.NodeList && !NodeList.prototype.forEach) {
        NodeList.prototype.forEach = Array.prototype.forEach;
    }

    /* ===== Execute ========================= */

    pure.Droploader = Droploader;
    pure.Droploader.ready(pure.Droploader.auto);

})(window.pure = window.pure || {});