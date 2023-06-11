define(['TYPO3/CMS/Backend/Modal', 'jquery', 'TYPO3/CMS/Backend/Icons', 'TYPO3/CMS/Core/Ajax/AjaxRequest'], (function (Modal, $, Icons, AjaxRequest) { 'use strict';

    function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }

    var Modal__default = /*#__PURE__*/_interopDefaultLegacy(Modal);
    var $__default = /*#__PURE__*/_interopDefaultLegacy($);
    var Icons__default = /*#__PURE__*/_interopDefaultLegacy(Icons);
    var AjaxRequest__default = /*#__PURE__*/_interopDefaultLegacy(AjaxRequest);

    class EmailWizard {
        constructor(typo3version, tableName, uid) {
            this.typo3version = typo3version;
            this.tableName = tableName;
            this.uid = uid;
            $__default["default"]('.viewmodule_email_button').on('click', this.onButtonClick.bind(this));
        }
        onButtonClick(e) {
            e.preventDefault();
            let url = TYPO3.settings.ajaxUrls.wizard_email_modal;
            url += "&tableName=" + encodeURIComponent(this.tableName);
            url += "&uid=" + encodeURIComponent(this.uid);
            this.currentModal = Modal__default["default"].advanced({
                type: 'ajax',
                content: url,
                size: Modal__default["default"].sizes.large,
                title: TYPO3.lang.bwemail_modalTitle,
                style: Modal__default["default"].styles.light,
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
                            Modal__default["default"].currentModal.trigger('modal-dismiss');
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
            });
        }
        onModalOpened() {
            // change dom elements
            this.loaderTarget = this.currentModal.find('#emailPreview');
            this.wizardSettingsForm = this.currentModal.find('#wizardSettingsForm');
            // adjust height of email preview
            setTimeout(() => {
                this.loaderTarget.css('height', this.currentModal.find('.modal-body').innerHeight() - 190);
            }, 100);
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
        onProviderSelectChange() {
            this.loadEmailPreview();
        }
        onProviderSwitch(e) {
            this.currentModal.find('.provider--input').addClass('hidden-by-provider-selection');
            const providerId = $__default["default"]('option:selected', e.currentTarget).attr('data-provider-index');
            this.currentModal.find('.provider--' + providerId).removeClass('hidden-by-provider-selection');
            this.loadEmailPreview();
        }
        toggleProviderView(e) {
            this.currentModal.find('.provider').toggleClass('hidden-by-count-toggle');
        }
        phoneClosingAnimation(e) {
            e.preventDefault();
            this.loaderTarget.toggleClass('closeing');
        }
        loadEmailPreview() {
            Icons__default["default"].getIcon('spinner-circle', Icons__default["default"].sizes.default, null, null, Icons__default["default"].markupIdentifiers.inline).done((icon) => {
                this.loaderTarget.html(icon);
                const form = this.currentModal.find('#wizardSettingsForm').get(0);
                const formData = new FormData(form);
                const formDataObject = Object.fromEntries(formData.entries());
                new AjaxRequest__default["default"](TYPO3.settings.ajaxUrls.email_preview)
                    .post(formDataObject)
                    .then(async (response) => {
                    const data = await response.resolve();
                    this.loaderTarget.html('<iframe frameborder="0" style="width:100%; height: ' + this.loaderTarget.css('height') + '" src="' + data.iframeSrc + '"></iframe>');
                    this.createMarkerFieldset(data);
                });
            });
        }
        showEmailPreview(createMarkerFieldset, data) {
            const $showUidInput = this.currentModal.find('#showUid-form-group');
            this.loaderTarget.html('<iframe frameborder="0" style="width:100%; height: ' + this.loaderTarget.css('height') + '" src="' + data.src + '"></iframe>');
            if (data.hasInternalLinks) {
                $showUidInput.show();
            }
            else {
                $showUidInput.hide();
            }
            // update contact list
            const contactSelection = this.currentModal.find('.provider--contacts:visible select');
            $__default["default"]('option', contactSelection).remove();
            for (let i = 0; i < data.contacts.length; i++) {
                let contact = $__default["default"]('<option value="' + i + '">' + data.contacts[i].email + '</option>');
                if (parseInt(data.selectedContact) === i) {
                    contact.attr('selected', 'selected');
                }
                contactSelection.append(contact);
            }
            if (createMarkerFieldset) {
                this.createMarkerFieldset(data);
            }
        }
        createMarkerFieldset(data) {
            const $markerFieldset = this.currentModal.find('#markerOverrideFieldset');
            $markerFieldset.html('');
            // template contains no markers
            if (!data.hasOwnProperty('marker') || !data.marker.length) {
                $markerFieldset.hide();
                return;
            }
            // create input fields und bind event to update preview
            for (let i = 0; i < data.marker.length; i++) {
                const m = data.marker[i];
                let $input = (m.content && m.content.length) > 25 ? $__default["default"]('<textarea />') : $__default["default"]('<input />');
                $input
                    .attr('name', 'wizardSettings[markerOverrides][' + m.name + ']')
                    .attr('id', 'wizardSettings[markerOverrides][' + m.name + ']')
                    .attr('placeholder', m.content)
                    .attr('class', 'form-control')
                    .bind('blur', this.onOverrideMarkerBlur.bind(this));
                if (m.override) {
                    $input.val(m.override);
                }
                $input = $input.wrap('<div class="form-control-wrap"></div>').parent();
                $input = $input.wrap('<div class="form-group"></div>').parent();
                $input.prepend('<label for="wizardSettings[markerOverrides][' + m.name + ']">' + m.name + ' override</label>');
                $markerFieldset.append($input);
            }
            $markerFieldset.show();
        }
        onOverrideMarkerBlur() {
            this.loadEmailPreview();
        }
        trySend(e) {
            let recipientText = this.currentModal.find('#recipientAddress').val();
            const multipleRecipients = this.currentModal.find('input[name="provider[use]"]:checked').val() === '1';
            if (multipleRecipients) {
                recipientText = this.currentModal.find('.provider--contacts:visible select option').length + ' recipients';
            }
            this.confirmModal = Modal__default["default"].advanced({
                title: 'Are you sure?',
                size: Modal__default["default"].sizes.small,
                style: Modal__default["default"].styles.dark,
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
        doSend() {
            Icons__default["default"].getIcon('spinner-circle', Icons__default["default"].sizes.default, null, null, Icons__default["default"].markupIdentifiers.inline).done((icon) => {
                this.confirmModal.find('.modal-title').html('Sending..');
                this.confirmModal.find('.modal-body').css('text-align', 'center').html(icon);
                const form = this.currentModal.find('#wizardSettingsForm').get(0);
                const formData = new FormData(form);
                const formDataObject = Object.fromEntries(formData.entries());
                new AjaxRequest__default["default"](TYPO3.settings.ajaxUrls.email_send)
                    .post(formDataObject)
                    .then(async (response) => {
                    const data = await response.resolve();
                    this.onSendResponse(data);
                });
            });
        }
        abortSend() {
            this.confirmModal.trigger('modal-dismiss');
        }
        onSendResponse(data) {
            this.confirmModal.trigger('modal-dismiss');
            data = JSON.parse(data);
            if (data.status === 'OK') {
                this.loaderTarget.addClass('closeing');
                setTimeout(function () {
                    this.currentModal.trigger('modal-dismiss');
                    top.TYPO3.Notification.success(data.message.headline, data.message.text);
                }.bind(this), 2000);
            }
            else if (data.status === 'WARNING') {
                top.TYPO3.Notification.warning(data.message.headline, data.message.text);
            }
            else {
                top.TYPO3.Notification.error(data.message.headline, data.message.text);
            }
        }
    }

    return EmailWizard;

}));
