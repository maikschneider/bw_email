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
	private $inbox: JQuery;
	private $message: JQuery;

	init() {
		this.cacheDom();
		this.bindEvents();
	}

	private cacheDom() {
		this.$previewButtons = $('.btn.preview');
		this.$inbox = $('#inbox');
		this.$message = $('#message');
	}

	private bindEvents() {
		this.$previewButtons.on('click', this.onPreviewButtonClick.bind(this));
		if (this.$inbox.length) {
			this.loadInbox();
		}
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

	private loadInbox() {
		const self = this;

		$.get(this.$inbox.attr('data-uri'), function (response) {
			self.$inbox.html(response.html);
			$('.message-item', self.$inbox).off('click').on('click', self.onMessageItemClick.bind(self));
		});

	}

	private onMessageItemClick(e) {
		const self = this;
		const $item = $(e.currentTarget);
		const postData = {
			messageNumber: $item.attr('data-mail-number'),
			messageMailbox: $item.attr('data-mail-mailbox')
		};

		$('.message-item', self.$inbox).removeClass('active');
		$item.addClass('active');

		$.post(TYPO3.settings.ajaxUrls['email_show'], postData, function (response) {
			self.$message.html(response.html);
		});

	}
}

export = new EmailModule().init();
