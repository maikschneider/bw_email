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
            this.currentMailbox = 'INBOX';
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
                this.loadMailbox();
            }
        };
        EmailModule.prototype.bindLoadMoreOnScrollEvent = function () {
            var self = this;
            function onScrollEvent() {
                if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 70) {
                    self.$inbox.off('scroll', onScrollEvent);
                    self.loadMailbox();
                }
            }
            this.$inbox.on('scroll', onScrollEvent);
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
        EmailModule.prototype.setLoading = function ($container, isLoading, loadingType) {
            if (loadingType === void 0) { loadingType = 'default'; }
            if (!isLoading) {
                $container.removeAttr('data-loading');
                return;
            }
            $container.attr('data-loading', loadingType);
        };
        EmailModule.prototype.loadMailbox = function () {
            var self = this;
            // gather message ids in current inbox
            var messageIds = [];
            $('.message-item', self.$inbox).each(function () {
                messageIds.push(parseInt($(this).attr('data-mail-number')));
            });
            var postData = {
                mailboxName: self.currentMailbox,
                messageIds: messageIds
            };
            self.setLoading(self.$inbox, true, postData.messageIds.length ? 'bottom' : '');
            $.post(this.$inbox.attr('data-uri'), postData, function (response) {
                // @TODO: sort emails in correct order
                self.$inbox.append(response.html);
                self.setLoading(self.$inbox, false);
                self.bindLoadMoreOnScrollEvent();
                $('.message-item', self.$inbox).off('click').on('click', self.onMessageItemClick.bind(self));
                if (!$('.message-item.active').length) {
                    $('.message-item', self.$inbox).first().trigger('click');
                }
            });
        };
        EmailModule.prototype.onMessageItemClick = function (e) {
            var self = this;
            var $item = $(e.currentTarget);
            var postData = {
                messageNumber: $item.attr('data-mail-number'),
                messageMailbox: $item.attr('data-mail-mailbox')
            };
            // save clicked message number
            this.currentMessage = parseInt(postData.messageNumber);
            $('.message-item', self.$inbox).removeClass('active');
            $item.addClass('active');
            // show spinner
            self.setLoading(this.$message, true);
            $.post(TYPO3.settings.ajaxUrls['email_show'], postData, function (response) {
                self.$message.html(response.html);
                self.setLoading(self.$message, false);
            });
        };
        return EmailModule;
    }());
    return new EmailModule().init();
});
