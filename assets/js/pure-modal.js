(function (pure) {
	'use strict'

	/* ===== Constructor ========================= */

	function Modal(target, options) {
		this.target = target;
		this.options = options;
		this.overlay = null;
		this.init();
	}

	Modal.prototype.init = function () {
		this.dispatch('pureModalInit', { modal: this });

		let trigger = document.querySelectorAll('[data-target=' + this.target.id + ']');
		for (let i = 0, len = trigger.length; i < len; i++) {
			trigger[i].addEventListener('click', this.open.bind(this));
		}

		this.dispatch('pureModalReady', { modal: this });
	}

	Modal.prototype.isOpen = function () {
		return Boolean(this.overlay);
	}

	Modal.prototype.open = function () {
		if (this.target && !this.isOpen()) {
			this.dispatch('pureModalOpen', { modal: this });

			const overlay = this.overlay = document.createElement('div');
			const dialog = this.overlay.appendChild(document.createElement('div'));
			const content = this.target;

			overlay.classList.add('pure-modal-overlay');
			dialog.classList.add('pure-modal-dialog');

			content.parentNode.insertBefore(overlay, content)
			dialog.appendChild(content);

			window.requestAnimationFrame(function (timestamp) {
				let diff = (overlay.clientHeight - dialog.scrollHeight) / 2;
				if (diff >= 0) dialog.style.marginTop = diff + 'px';
				this.removeScroll();
				this.overlay.classList.add('open');
			}.bind(this));

			window.addEventListener('resize', function (event) {
				window.requestAnimationFrame(function (timestamp) {
					let diff = (overlay.clientHeight - dialog.scrollHeight) / 2;
					if (diff >= 0) dialog.style.marginTop = diff + 'px';
					this.removeScroll();
				}.bind(this));
			}.bind(this));

			dialog.addEventListener('click', function (event) {
				event.stopPropagation();
			});

			overlay.addEventListener('click', this.close.bind(this));
		}
	}

	Modal.prototype.close = function () {
		if (this.target && this.isOpen()) {
			this.dispatch('pureModalClose', { modal: this });
			this.overlay.firstElementChild.style.marginTop = 0;
			this.overlay.classList.remove('open');

			setTimeout(function () {
				this.overlay.parentNode.insertBefore(this.target, this.overlay);
				this.overlay.parentNode.removeChild(this.overlay);
				this.overlay = null;
				this.resetScroll();
			}.bind(this), 250);
		}
	}

	/* ===== Utils ========================= */

	Modal.prototype.extend = function () {
		for (var i = 1; i < arguments.length; i++) {
			for (var key in arguments[i]) {
				if (arguments[i].hasOwnProperty(key)) {
					arguments[0][key] = arguments[i][key]
				}
			}
		}
		return arguments[0];
	}

	Modal.prototype.dispatch = function (type, detail) {
		this.target.dispatchEvent(new CustomEvent(type, {
			bubbles: true,
			detail: detail,
		}));
	}

	Modal.prototype.removeScroll = function () {
		var scrollbar = (window.innerWidth - document.documentElement.clientWidth) + 'px';
		document.documentElement.style.overflow = 'hidden';
		document.body.style.overflow = 'hidden';
		document.body.style.paddingRight = scrollbar;
	}

	Modal.prototype.resetScroll = function () {
		document.body.style.paddingRight = '';
		document.body.style.overflow = '';
		document.documentElement.style.overflow = '';
	}

	/* ===== Polyfills ========================= */

	if (typeof (window.CustomEvent) !== "function") {
		window.CustomEvent = function (type, params) {
			params = params || { bubbles: false, cancelable: false, detail: null };
			var event = document.createEvent('CustomEvent');
			event.initCustomEvent(type, params.bubbles, params.cancelable, params.detail);
			return event;
		}
	}

	/* ===== Static methods ========================= */

	Object.defineProperty(Modal, 'auto', {
		configurable: true,
		value: function () {
			const target = document.querySelectorAll('.pure-modal-target');
			for (let i = 0, len = target.length; i < len; i++) {
				//	new Modal(target[i], JSON.parse(target[i].dataset.pureModal));
				new Modal(target[i]);
			}
		}
	});

	Object.defineProperty(Modal, 'ready', {
		configurable: true,
		value: function (callback) {
			document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', callback) : callback();
		},
	});

	Modal.ready(Modal.auto);
	pure.Modal = Modal;

})(window.pure = window.pure || {});