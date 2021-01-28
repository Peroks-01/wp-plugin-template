(function (pure) {
	'use strict'

	/* ===== Constructor and initiation ========================= */

	function Modal(container, options) {
		this.options = options || {};
		this.container = container;
		this.overlay = null;
		this.dialog = null;
		this.init();
	}

	Modal.prototype.init = function () {
		this.dispatch('pureModalInit', { modal: this });
		const id = this.container.getAttribute('id');

		if (Boolean(id)) {
			document.querySelectorAll('.' + id).forEach(function (trigger) {
				trigger.addEventListener('click', function (event) {
					event.preventDefault();
					event.stopPropagation();
					this.open.call(this);
				}.bind(this));
			}, this);
		}

		this.container.querySelectorAll('data.pure-modal-load').forEach(function (data) {
			this.load(data.value, function (status, response) {
				let detail = { modal: this, data: data, status: status, response: response };
				this.dispatch('pureModalLoaded', detail);
				if (detail.status == 200) data.outerHTML = detail.response;
			});
		}, this);

		this.dispatch('pureModalReady', { modal: this });
	}

	/* ===== Open and close modal dialog ========================= */

	Modal.prototype.open = function () {
		if (this.container && !this.overlay) {
			this.dispatch('pureModalOpen', { modal: this });

			this.container.querySelectorAll('data.pure-modal-defer').forEach(function (data) {
				data.parentNode.insertBefore(Modal.parse(atob(data.value)), data);
				data.parentNode.removeChild(data);
			}, this);

			this.build();

			this.dialog.addEventListener('click', function (event) { event.stopPropagation() });
			this.overlay.addEventListener('click', this.close.bind(this));
			this.overlay.querySelectorAll('.pure-modal-close').forEach(function (elem) {
				elem.addEventListener('click', this.close.bind(this));
			}, this);

			window.addEventListener('resize', function (event) {
				window.requestAnimationFrame(function (timestamp) {
					this.setPosition();
					this.removeScroll();
				}.bind(this));
			}.bind(this));
		}

		this.dispatch('pureModalOpened', { modal: this });
	}

	Modal.prototype.build = function () {
		this.overlay = document.createElement('div');
		this.overlay.setAttribute('class', 'pure-modal-overlay');
		this.overlay.setAttribute('style', 'position: fixed; top: 0; right: 0; bottom: 0; left: 0; overflow-x: hidden; overflow-y: auto; -webkit-overflow-scrolling: touch');

		this.dialog = this.overlay.appendChild(document.createElement('div'));
		this.dialog.setAttribute('class', 'pure-modal-dialog');
		this.dialog.setAttribute('style', 'position: relative; margin: 0 auto');

		const close = this.dialog.appendChild(document.createElement('div'));
		close.setAttribute('class', 'pure-modal-close');
		close.textContent = '×';

		this.container.parentNode.insertBefore(this.overlay, this.container)
		this.dialog.appendChild(this.container);

		this.setPosition();
		this.removeScroll();
		this.overlay.classList.add('open');
	}

	Modal.prototype.close = function () {
		if (this.container && this.overlay) {
			this.overlay.classList.remove('open');
			this.dialog.style.marginTop = 0;
			this.dispatch('pureModalClose', { modal: this });

			setTimeout(function () {
				this.overlay.parentNode.insertBefore(this.container, this.overlay);
				this.overlay.parentNode.removeChild(this.overlay);
				this.overlay = this.dialog = null;
				this.resetScroll();
				this.dispatch('pureModalClosed', { modal: this });
			}.bind(this), this.options.fadeout || 250);
		}
	}

	/* ===== Utils ========================= */

	Modal.prototype.setPosition = function () {
		const diff = (this.overlay.clientHeight - this.dialog.scrollHeight) >> 1;
		const pad = parseInt(window.getComputedStyle(this.overlay).paddingTop);
		this.dialog.style.marginTop = Math.max(diff - pad, 0) + 'px';
	}

	Modal.prototype.removeScroll = function () {
		const scrollbar = window.innerWidth - document.documentElement.clientWidth;
		document.documentElement.style.overflow = 'hidden';
		document.body.style.overflow = 'hidden';
		document.body.style.paddingRight = scrollbar + 'px';
	}

	Modal.prototype.resetScroll = function () {
		document.body.style.paddingRight = '';
		document.body.style.overflow = '';
		document.documentElement.style.overflow = '';
	}

	Modal.prototype.dispatch = function (type, detail) {
		this.container.dispatchEvent(new CustomEvent(type, {
			bubbles: true,
			detail: detail,
		}));
	}

	Modal.prototype.load = function (url, callback) {
		var xhr = new XMLHttpRequest();
		xhr.open('GET', url);
		xhr.send();
		xhr.onload = function (event) {
			callback.call(this, xhr.status, xhr.responseText);
		}.bind(this);
	}

	/* ===== Static methods ========================= */

	Object.defineProperty(Modal, 'parse', {
		configurable: true,
		value: function (content) {
			if (content && typeof (content) === 'string') {
				var target = document.createDocumentFragment();
				var source = document.createElement('div');
				source.innerHTML = content;

				while (source.children.length) {
					if (source.children[0].tagName.toLowerCase() == 'script') {
						target.appendChild(Modal.clone(source.children[0]));
						source.removeChild(source.children[0]);
					} else {
						target.appendChild(source.children[0]);
					}
				}
				return target;
			}
			return content || document.createDocumentFragment();
		}
	});

	Object.defineProperty(Modal, 'clone', {
		configurable: true,
		value: function (source) {
			var target = document.createElement(source.tagName.toLowerCase());
			target.textContent = source.textContent;

			for (var i = 0, len = source.attributes.length; i < len; i++) {
				target.setAttribute(source.attributes[i].nodeName, source.attributes[i].nodeValue);
			}
			return target;
		}
	});

	Object.defineProperty(Modal, 'auto', {
		configurable: true,
		value: function () {
			document.querySelectorAll('.pure-modal-container').forEach(function (container) {
				let options = JSON.parse(container.dataset.pureModal || '{}');
				new Modal(container, options);
			});
		}
	});

	Object.defineProperty(Modal, 'ready', {
		configurable: true,
		value: function (callback) {
			document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', callback) : callback();
		}
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

	pure.Modal = Modal;
	pure.Modal.ready(pure.Modal.auto);

})(window.pure = window.pure || {});