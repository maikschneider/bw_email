import Modal from '@typo3/backend/modal.js'
import $ from 'jquery'
import Icons from '@typo3/backend/icons.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import AjaxResponse from "@typo3/core/ajax/ajax-response.js";

class EmailWizard {

	private typo3version: number;
	private tableName: string;
	private uid: number;

	private currentModal: JQuery;
	private confirmModal: JQuery;
	private loaderTarget: JQuery;
	private wizardSettingsForm: JQuery;

	constructor(typo3version: number, tableName: string, uid: number) {
		this.typo3version = typo3version;
		this.tableName = tableName;
		this.uid = uid;

		$('.viewmodule_email_button').on('click', this.onButtonClick.bind(this));
	}

	private onButtonClick(e: JQueryEventObject) {
		e.preventDefault()

		let url = TYPO3.settings.ajaxUrls.wizard_email_modal;
		url += "&tableName=" + encodeURIComponent(this.tableName);
		url += "&uid=" + encodeURIComponent(this.uid);

		this.currentModal = Modal.advanced({
			type: 'ajax',
			content: url,
			size: Modal.sizes.large,
			title: TYPO3.lang.bwemail_modalTitle,
			style: Modal.styles.light,
			ajaxCallback: this.onModalOpened.bind(this),
			buttons: [
				{
					text: TYPO3.lang.bwemail_modalCancelButton,
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
					text: TYPO3.lang.bwemail_modalSendButton,
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
		// change dom elements
		this.loaderTarget = this.currentModal.find('#emailPreview');
		this.wizardSettingsForm = this.currentModal.find('#wizardSettingsForm');

		// adjust height of email preview
		setTimeout(() => {
			this.loaderTarget.css('height', this.currentModal.find('.modal-body').innerHeight() - 190);
		}, 100)

		// onload first template
		this.loadEmailPreview();

		// bind template change event
		this.currentModal.find('select#template').on('change', function (el) {
			this.loadEmailPreview();
		}.bind(this));

		// bind home button event
		this.currentModal.find('#phoneCloseButton').on('click', this.phoneClosingAnimation.bind(this));

		// bind provider radio toggle
		this.currentModal.find('input[name="provider[use]"]').on('change', this.toggleProviderView.bind(this));

		// bind provider selection
		this.currentModal.find('select[name="provider[id]"]').on('change', this.onProviderSwitch.bind(this));

		// bind provider option change
		this.currentModal.find('.provider--input select').on('change', this.onProviderSelectChange.bind(this));

	}

	private onProviderSelectChange() {
		this.refreshEmailPreview();
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
		this.loaderTarget.toggleClass('closeing');
	}

	private loadEmailPreview() {
		Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done((icon: string): void => {
			this.loaderTarget.html(icon);

			const form = this.currentModal.find('#wizardSettingsForm').get(0) as HTMLFormElement;
			const formData = new FormData(form);
			const formDataObject = Object.fromEntries(formData.entries());

			new AjaxRequest(TYPO3.settings.ajaxUrls.email_preview)
				.post(formDataObject)
				.then(async (response: AjaxResponse): Promise<void> => {
					const data = await response.resolve();
					this.loaderTarget.html('<iframe frameborder="0" style="width:100%; height: ' + this.loaderTarget.css('height') + '" src="' + data.iframeSrc + '"></iframe>');
					this.createMarkerFieldset(data);
				});
		});
	}

	private showEmailPreview(createMarkerFieldset, data) {
		const $showUidInput = this.currentModal.find('#showUid-form-group');

		this.loaderTarget.html('<iframe frameborder="0" style="width:100%; height: ' + this.loaderTarget.css('height') + '" src="' + data.src + '"></iframe>');

		if (data.hasInternalLinks) {
			$showUidInput.show();
		} else {
			$showUidInput.hide();
		}

		// update contact list
		const contactSelection = this.currentModal.find('.provider--contacts:visible select');
		$('option', contactSelection).remove();
		for (let i = 0; i < data.contacts.length; i++) {
			let contact = $('<option value="' + i + '">' + data.contacts[i].email + '</option>');
			if (parseInt(data.selectedContact) === i) {
				contact.attr('selected', 'selected');
			}
			contactSelection.append(contact);
		}

		if (createMarkerFieldset) {
			this.createMarkerFieldset(data);
		}
	}

	private createMarkerFieldset(data) {
		const $markerFieldset = this.currentModal.find('#markerOverrideFieldset');
		$markerFieldset.html('');

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
				.attr('name', 'wizardSettings[markerOverrides][' + m.name + ']')
				.attr('id', 'wizardSettings[markerOverrides][' + m.name + ']')
				.attr('placeholder', m.content)
				.attr('class', 'form-control')
				.bind('blur', this.onOverrideMarkerBlur.bind(this));

			$input = $input.wrap('<div class="form-control-wrap"></div>').parent();
			$input = $input.wrap('<div class="form-group"></div>').parent();
			$input.prepend('<label for="wizardSettings[markerOverrides][' + m.name + ']">' + m.name + ' override</label>');

			$markerFieldset.append($input);
		}

		$markerFieldset.show();
	}

	private onOverrideMarkerBlur() {
		this.loadEmailPreview();
	}

	private refreshEmailPreview() {
		const templateSelector = this.currentModal.find('select#template');
		const previewUri = templateSelector.find('option:selected').data('preview-uri');

		Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done((icon: string): void => {
			this.loaderTarget.html(icon);
			$.post(
				previewUri,
				this.currentModal.find('#markerOverrideFieldset input, #markerOverrideFieldset textarea, [name^="provider"]').serializeArray(),
				this.showEmailPreview.bind(this, false),
				'json'
			);
		});
	}

	private trySend(e: JQueryEventObject) {

		let recipientText = this.currentModal.find('#recipientAddress').val();

		const multipleRecipients = this.currentModal.find('input[name="provider[use]"]:checked').val() === '1';
		if (multipleRecipients) {
			recipientText = this.currentModal.find('.provider--contacts:visible select option').length + ' recipients';
		}

		this.confirmModal = Modal.advanced({
			title: 'Are you sure?',
			size: Modal.sizes.small,
			style: Modal.styles.dark,
			content: 'You are going to send the displayed HTML mail to ' + recipientText + '.',
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

			const form = this.currentModal.find('#wizardSettingsForm').get(0) as HTMLFormElement;
			const formData = new FormData(form);
			const formDataObject = Object.fromEntries(formData.entries());

			new AjaxRequest(TYPO3.settings.ajaxUrls.email_send)
				.post(formDataObject)
				.then(async (response: AjaxResponse): Promise<void> => {
					const data = await response.resolve();
					this.onSendResponse(data);
				});
		});

	}

	private abortSend() {
		this.confirmModal.trigger('modal-dismiss');
	}

	private onSendResponse(data) {

		this.confirmModal.trigger('modal-dismiss');

		if (data.status === 'OK') {

			this.loaderTarget.addClass('closeing');
			setTimeout(function () {
				this.currentModal.trigger('modal-dismiss');
				top.TYPO3.Notification.success(data.message.headline, data.message.text);
			}.bind(this), 2000);

		} else if (data.status === 'WARNING') {
			top.TYPO3.Notification.warning(data.message.headline, data.message.text);
		} else {
			top.TYPO3.Notification.error(data.message.headline, data.message.text);
		}


	}

}

export default EmailWizard
