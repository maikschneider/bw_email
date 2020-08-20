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
            this.$inbox = $('#inbox');
            this.$message = $('#message');
        };
        EmailModule.prototype.bindEvents = function () {
            this.$previewButtons.on('click', this.onPreviewButtonClick.bind(this));
            if (this.$inbox.length) {
                this.loadInbox();
            }
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
        EmailModule.prototype.loadInbox = function () {
            var self = this;
            $.get(this.$inbox.attr('data-uri'), function (response) {
                self.$inbox.html(response.html);
                $('.message-item', self.$inbox).off('click').on('click', self.onMessageItemClick.bind(self));
                $('.message-item', self.$inbox).first().trigger('click');
            });
        };
        EmailModule.prototype.onMessageItemClick = function (e) {
            var self = this;
            var $item = $(e.currentTarget);
            var postData = {
                messageNumber: $item.attr('data-mail-number'),
                messageMailbox: $item.attr('data-mail-mailbox')
            };
            $('.message-item', self.$inbox).removeClass('active');
            $item.addClass('active');
            // show spinner
            self.$message.html('<i class="fa fa-spinner fa-spin"></i>');
            $.post(TYPO3.settings.ajaxUrls['email_show'], postData, function (response) {
                self.$message.html(response.html);
            });
        };
        return EmailModule;
    }());
    return new EmailModule().init();
});
