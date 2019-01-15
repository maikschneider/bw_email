define(["require", "exports", "TYPO3/CMS/Backend/Modal", "jquery", "TYPO3/CMS/Backend/Icons"], function (require, exports, Modal, $, Icons) {
    "use strict";
    /**
     * Module: TYPO3/CMS/BwEmail/EmailWizard
     *
     * @exports TYPO3/CMS/BwEmail/EmailWizard
     */
    var EmailWizard = /** @class */ (function () {
        function EmailWizard() {
        }
        EmailWizard.prototype.init = function () {
            this.cacheElements();
            this.initEvents();
        };
        EmailWizard.prototype.cacheElements = function () {
            this.$viewModuleButton = $('.viewmodule_email_button');
            this.$sendMailButton = $('#sendMailButton');
        };
        EmailWizard.prototype.initEvents = function () {
            this.$sendMailButton.on('click', this.onButtonClick.bind(this));
            this.$viewModuleButton.on('click', this.onButtonClick.bind(this));
        };
        EmailWizard.prototype.onButtonClick = function (e) {
            e.preventDefault();
            // collect modal infos
            var wizardUri = this.$viewModuleButton.data('wizard-uri');
            var modalTitle = this.$viewModuleButton.data('modal-title');
            var modalCancelButtonText = this.$viewModuleButton.data('modal-cancel-button-text');
            var modalSendButtonText = this.$viewModuleButton.data('modal-send-button-text');
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
            });
        };
        EmailWizard.prototype.onModalOpened = function () {
            this.$loaderTarget = this.currentModal.find('#emailPreview');
            this.$loaderTarget.css('height', this.currentModal.find('.modal-body').innerHeight() - 190);
            var templateSelector = this.currentModal.find('select#template');
            var previewUri = templateSelector.find('option:selected').data('preview-uri');
            var $closeButton = this.currentModal.find('#phoneCloseButton');
            // onload first template
            this.loadEmailPreview(previewUri);
            // bind template change event
            templateSelector.on('change', function (el) {
                var previewUri = $(el.currentTarget).find('option:selected').data('preview-uri');
                var $markerFieldset = this.currentModal.find('#markerOverrideFieldset');
                // reset override fields
                $markerFieldset.html('');
                // load first preview
                this.loadEmailPreview(previewUri, true);
            }.bind(this));
            // bind home button event
            $closeButton.on('click', this.phoneClosingAnimation.bind(this));
            // bind provider radio toggle
            this.currentModal.find('input[name="showprovider"]').on('change', this.toggleProviderView.bind(this));
        };
        EmailWizard.prototype.toggleProviderView = function (e) {
            this.currentModal.find('.provider').toggleClass('hidden-by-count-toggle');
        };
        EmailWizard.prototype.phoneClosingAnimation = function (e) {
            e.preventDefault();
            this.$loaderTarget.toggleClass('closeing');
        };
        EmailWizard.prototype.loadEmailPreview = function (uri) {
            var _this = this;
            Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done(function (icon) {
                _this.$loaderTarget.html(icon);
                $.get(uri, _this.showEmailPreview.bind(_this, true), 'json');
            });
        };
        EmailWizard.prototype.showEmailPreview = function (createMarkerFieldset, data) {
            var $showUidInput = this.currentModal.find('#showUid-form-group');
            this.$loaderTarget.html('<iframe frameborder="0" style="width:100%; height: ' + this.$loaderTarget.css('height') + '" src="' + data.src + '"></iframe>');
            if (data.hasInternalLinks) {
                $showUidInput.show();
            }
            else {
                $showUidInput.hide();
            }
            if (createMarkerFieldset) {
                this.createMarkerFieldset(data);
            }
        };
        EmailWizard.prototype.createMarkerFieldset = function (data) {
            var $markerFieldset = this.currentModal.find('#markerOverrideFieldset');
            // template contains no markers
            if (!data.hasOwnProperty('marker') || !data.marker.length) {
                $markerFieldset.html('');
                $markerFieldset.hide();
                return;
            }
            // create input fields und bind event to update preview
            for (var i = 0; i < data.marker.length; i++) {
                var m = data.marker[i];
                var $input = (m.content && m.content.length) > 25 ? $('<textarea />') : $('<input />');
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
        };
        EmailWizard.prototype.onOverrideMarkerBlur = function () {
            var _this = this;
            var templateSelector = this.currentModal.find('select#template');
            var previewUri = templateSelector.find('option:selected').data('preview-uri');
            Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done(function (icon) {
                _this.$loaderTarget.html(icon);
                $.post(previewUri, _this.currentModal.find('#markerOverrideFieldset input, #markerOverrideFieldset textarea').serializeArray(), _this.showEmailPreview.bind(_this, false), 'json');
            });
        };
        EmailWizard.prototype.trySend = function (e) {
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
        };
        EmailWizard.prototype.doSend = function () {
            var _this = this;
            Icons.getIcon('spinner-circle', Icons.sizes.default, null, null, Icons.markupIdentifiers.inline).done(function (icon) {
                _this.confirmModal.find('.modal-title').html('Sending..');
                _this.confirmModal.find('.modal-body').css('text-align', 'center').html(icon);
                $.post(_this.currentModal.find('form').attr('action'), _this.currentModal.find('form').serialize(), _this.onSendResponse.bind(_this), 'json');
            });
        };
        EmailWizard.prototype.abortSend = function () {
            this.confirmModal.trigger('modal-dismiss');
        };
        EmailWizard.prototype.onSendResponse = function (data) {
            this.confirmModal.trigger('modal-dismiss');
            this.$loaderTarget.addClass('closeing');
            setTimeout(function () {
                this.currentModal.trigger('modal-dismiss');
                if (data.status === 'OK') {
                    top.TYPO3.Notification.success(data.message.headline, data.message.text);
                }
                else if (data.status === 'WARNING') {
                    top.TYPO3.Notification.warning(data.message.headline, data.message.text);
                }
                else {
                    top.TYPO3.Notification.error(data.message.headline, data.message.text);
                }
            }.bind(this), 2000);
        };
        return EmailWizard;
    }());
    return new EmailWizard().init();
});
