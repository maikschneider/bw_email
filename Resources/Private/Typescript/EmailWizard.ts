import Modal = require('TYPO3/CMS/Backend/Modal');
import $ = require('jquery');
import Icons = require('TYPO3/CMS/Backend/Icons');


declare global {
	interface Window {
		TYPO3: any;
	}
}


/**
 * Module: TYPO3/CMS/BwEmail/EmailWizard
 *
 * @exports TYPO3/CMS/BwEmail/EmailWizard
 */
class EmailWizard {

	private $sendMailButton: JQuery;
	private $viewModuleButton: JQuery;
	private currentModal: JQuery;
	private confirmModal: JQuery;
	private $loaderTarget: JQuery;

	public init() {
		this.cacheElements();
		this.initEvents();
	}

	private cacheElements() {
		this.$viewModuleButton = $('.viewmodule_email_button');
		this.$sendMailButton = $('#sendMailButton');
	}

	private initEvents() {
		this.$sendMailButton.on('click', this.onButtonClick.bind(this));
		this.$viewModuleButton.on('click', this.onButtonClick.bind(this));
	}

	private onButtonClick(e: JQueryEventObject) {
		e.preventDefault();
		// collect modal infos
		const wizardUri = this.$viewModuleButton.data('wizard-uri');
		const modalTitle = this.$viewModuleButton.data('modal-title');
		const modalCancelButtonText = this.$viewModuleButton.data('modal-cancel-button-text');
		const modalSendButtonText = this.$viewModuleButton.data('modal-send-button-text');

		this.currentModal = Modal.advanced({
			type: 'ajax',
			content: wizardUri,
			size: Modal.sizes.large,
			title: modalTitle,
			style: Modal.styles.light,
			ajaxCallback: this.onModalOpened.bind(this),
			buttons: [
				{
					text: modalCancelButtonText,
					name: 'dismiss',
					icon: 'actions-close',
					btnClass: 'btn-default',
					dataAttributes: {
						action: 'dismiss'
					},
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
					}
				},
				{
					text: modalSendButtonText,
					name: 'save',
					icon: 'actions-check',
					active: true,
					btnClass: 'btn-primary',
					dataAttributes: {
						action: 'save'
					},
					trigger: this.trySend.bind(this)
				}
			]

		})
	}

	private onModalOpened() {

		this.$loaderTarget = this.currentModal.find('#emailPreview');
		this.$loaderTarget.css('height', this.currentModal.find('.modal-body').innerHeight() - 190);

		const templateSelector = this.currentModal.find('select#template');
		const previewUri = templateSelector.find('option:selected').data('preview-uri');
		const $closeButton = this.currentModal.find('#phoneCloseButton');

		// onload first template
		this.loadEmailPreview(previewUri);

		// bind template change event
		templateSelector.on('change', function (el) {
			const previewUri = $(el.currentTarget).find('option:selected').data('preview-uri');
			const $markerFieldset = this.currentModal.find('#markerOverrideFieldset');

			// reset override fields
			$markerFieldset.html('');
			// load first preview
			this.loadEmailPreview(previewUri, true);
		}.bind(this));

		// bind home button event
		$closeButton.on('click', this.phoneClosingAnimation.bind(this));

		// bind provider radio toggle
		this.currentModal.find('input[name="provider[use]"]').on('change', this.toggleProviderView.bind(this));

		// bind provider selection
		this.currentModal.find('select[name="provider[id]"]').on('change', this.onProviderSwitch.bind(this));

	}

	private onProviderSwitch(e: JQueryEventObject) {
		this.currentModal.find('.provider--input').addClass('hidden-by-provider-selection');
		const providerId = $('option:selected', e.currentTarget).attr('data-provider-index');
		this.currentModal.find('.provider--' + providerId).removeClass('hidden-by-provider-selection');

		this.refreshEmailPreview();
	}

	private toggleProviderView(e: JQueryEventObject) {
		this.currentModal.find('.provider').toggleClass('hidden-by-count-toggle');
	}

	private phoneClosingAnimation(e: JQueryEventObject) {
		e.preventDefault();
		this.$loaderTarget.toggleClass('closeing');
	}

	private loadEmailPreview(uri) {
		Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done((icon: string): void => {
			this.$loaderTarget.html(icon);
			$.get(
				uri,
				this.showEmailPreview.bind(this, true),
				'json'
			);
		});
	}

	private showEmailPreview(createMarkerFieldset, data) {
		const $showUidInput = this.currentModal.find('#showUid-form-group');

		this.$loaderTarget.html('<iframe frameborder="0" style="width:100%; height: ' + this.$loaderTarget.css('height') + '" src="' + data.src + '"></iframe>');

		if (data.hasInternalLinks) {
			$showUidInput.show();
		} else {
			$showUidInput.hide();
		}

		if (createMarkerFieldset) {
			this.createMarkerFieldset(data);
		}
	}

	private createMarkerFieldset(data) {
		const $markerFieldset = this.currentModal.find('#markerOverrideFieldset');

		// template contains no markers
		if (!data.hasOwnProperty('marker') || !data.marker.length) {
			$markerFieldset.html('');
			$markerFieldset.hide();
			return;
		}

		// create input fields und bind event to update preview
		for (let i = 0; i < data.marker.length; i++) {
			const m = data.marker[i];
			let $input = (m.content && m.content.length) > 25 ? $('<textarea />') : $('<input />');
			$input
				.attr('name', 'markerOverrides[' + m.name + ']')
				.attr('id', 'markerOverrides[' + m.name + ']')
				.attr('placeholder', m.content)
				.attr('class', 'form-control')
				.bind('blur', this.onOverrideMarkerBlur.bind(this));

			$input = $input.wrap('<div class="form-control-wrap"></div>').parent();
			$input = $input.wrap('<div class="form-group"></div>').parent();
			$input.prepend('<label for="markerOverrides[' + m.name + ']">' + m.name + ' override</label>');

			$markerFieldset.append($input);
		}

		$markerFieldset.show();
	}

	private onOverrideMarkerBlur() {
		this.refreshEmailPreview();
	}

	private refreshEmailPreview() {
		const templateSelector = this.currentModal.find('select#template');
		const previewUri = templateSelector.find('option:selected').data('preview-uri');

		Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done((icon: string): void => {
			this.$loaderTarget.html(icon);
			$.post(
				previewUri,
				this.currentModal.find('#markerOverrideFieldset input, #markerOverrideFieldset textarea, [name^="provider"]').serializeArray(),
				this.showEmailPreview.bind(this, false),
				'json'
			);
		});
	}

	private trySend(e: JQueryEventObject) {

		this.confirmModal = Modal.advanced({
			title: 'Are you sure?',
			size: Modal.sizes.small,
			style: Modal.styles.dark,
			content: '<p>You are going to send the displayed HTML mail to <strong>' + this.currentModal.find('#recipientAddress').val() + '</strong></p>',
			buttons: [
				{
					text: 'Yes, send',
					name: 'save',
					icon: 'actions-check',
					btnClass: 'btn-success',
					dataAttributes: {
						action: 'save'
					},
					trigger: this.doSend.bind(this)
				},
				{
					text: 'No, abbort',
					name: 'dismiss',
					icon: 'actions-close',
					btnClass: 'btn-danger',
					dataAttributes: {
						action: 'dismiss'
					},
					trigger: this.abortSend.bind(this)
				},
			],
		});

	}

	private doSend() {

		Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done((icon: string): void => {
			this.confirmModal.find('.modal-title').html('Sending..');
			this.confirmModal.find('.modal-body').css('text-align', 'center').html(icon);
			$.post(
				this.currentModal.find('form').attr('action'),
				this.currentModal.find('form').serialize(),
				this.onSendResponse.bind(this),
				'json'
			);
		});

	}

	private abortSend() {
		this.confirmModal.trigger('modal-dismiss');
	}

	private onSendResponse(data) {

		this.confirmModal.trigger('modal-dismiss');
		this.$loaderTarget.addClass('closeing');

		setTimeout(function () {

			this.currentModal.trigger('modal-dismiss');

			if (data.status === 'OK') {
				top.TYPO3.Notification.success(data.message.headline, data.message.text);
			} else if (data.status === 'WARNING') {
				top.TYPO3.Notification.warning(data.message.headline, data.message.text);
			} else {
				top.TYPO3.Notification.error(data.message.headline, data.message.text);
			}

		}.bind(this), 2000);


	}

}

export = new EmailWizard().init();
