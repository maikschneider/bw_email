define(["require", "exports", "TYPO3/CMS/Backend/Modal", "jquery"], function (require, exports, Modal, $) {
    "use strict";
    /**
     * Module: TYPO3/CMS/BwEmail/EmailModule
     *
     * @exports TYPO3/CMS/BwEmail/EmailModule
     */
    var EmailModule = /** @class */ (function () {
        function EmailModule() {
        }
        EmailModule.prototype.init = function () {
            this.cacheDom();
            this.bindEvents();
        };
        EmailModule.prototype.cacheDom = function () {
            this.$previewButtons = $('.btn.preview');
        };
        EmailModule.prototype.bindEvents = function () {
            this.$previewButtons.on('click', this.onPreviewButtonClick.bind(this));
        };
        EmailModule.prototype.onPreviewButtonClick = function (e) {
            e.preventDefault();
            var self = this;
            var link = $(e.currentTarget).attr('href');
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
        };
        return EmailModule;
    }());
    return new EmailModule().init();
});
