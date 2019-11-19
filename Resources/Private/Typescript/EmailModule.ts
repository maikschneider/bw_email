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

	private onPreviewOpened() {

	}

	private onPreviewButtonClick(e: JQueryEventObject) {
		e.preventDefault();
		const link = $(e.currentTarget).attr('href');

		const modal = Modal.advanced({
			type: 'ajax',
			content: link,
			size: Modal.sizes.large,
			title: 'Email',
			style: Modal.styles.light,
			ajaxCallback: this.onPreviewOpened.bind(this),
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

		})
	}
}

export = new EmailModule().init();
