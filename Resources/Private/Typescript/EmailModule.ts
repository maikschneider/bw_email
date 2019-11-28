import Modal = require('TYPO3/CMS/Backend/Modal');
import $ = require('jquery');


declare global {
	interface Window {
		TYPO3: any;
	}
}


/**
 * Module: TYPO3/CMS/BwEmail/EmailModule
 *
 * @exports TYPO3/CMS/BwEmail/EmailModule
 */
class EmailModule {

	private $previewButtons: JQuery;
	private $modal: JQuery;

	init() {
		this.cacheDom();
		this.bindEvents();
	}

	private cacheDom() {
		this.$previewButtons = $('.btn.preview');
	}

	private bindEvents() {
		this.$previewButtons.on('click', this.onPreviewButtonClick.bind(this));
	}

	private onPreviewButtonClick(e: JQueryEventObject) {
		e.preventDefault();
		const self = this;
		const link = $(e.currentTarget).attr('href');

		self.$modal = Modal.advanced({
			type: 'content',
			content: 'Loading..',
			size: Modal.sizes.large,
			buttons: [
				{
					text: 'close',
					name: 'dismiss',
					icon: 'actions-close',
					btnClass: 'btn-default',
					dataAttributes: {
						action: 'dismiss'
					},
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
					}
				}
			]

		});

		$.get(link, function (response) {
			self.$modal.find('.t3js-modal-body').html('<iframe frameborder="0" width="100%" height="97%" src="' + response.src + '"></iframe>');
		});
	}
}

export = new EmailModule().init();
