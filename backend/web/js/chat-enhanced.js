$(document).ready(function() {
    var lastMessageId = window.chatConfig ? window.chatConfig.lastMessageId : 0;
    var totalMessages = window.chatConfig ? window.chatConfig.totalMessages : 0;
    var loadedMessages = window.chatConfig ? window.chatConfig.loadedMessages : 0;
    var selectedChatId = window.chatConfig ? window.chatConfig.selectedChatId : null;
    var isLoading = false;
    var isSending = false;
    var attachedFiles = [];
    var replyToMessageId = null;

    // Search functionality variables
    var originalMessages = null;
    var isSearchMode = false;

    // Check if we're on a chat page with form
    var hasChatForm = $("#message-input").length > 0;

    // Mobile chat state management
    function updateMobileChatState() {
        var isMobile = window.innerWidth <= 768;
        var chatLayout = $('.chat-layout');

        if (isMobile && selectedChatId) {
            // Add mobile class for hiding sidebar
            chatLayout.addClass('chat-selected');
        } else {
            // Remove mobile class
            chatLayout.removeClass('chat-selected');
        }
    }

    // Update mobile state on load and resize
    updateMobileChatState();
    $(window).resize(updateMobileChatState);

    // Escape HTML function (make it globally available)
    window.escapeHtml = function(text) {
        return $("<div>").text(text).html();
    };

    // Open image in modal (fixed version)
    window.openImageModal = function(imageUrl, imageName) {
        // Remove existing modal if any
        $('#imageModal').remove();

        // Create modal HTML
        var modalHtml =
            '<div class="modal fade" id="imageModal" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog modal-lg modal-dialog-centered" role="document">' +
            '<div class="modal-content bg-dark">' +
            '<div class="modal-header border-0">' +
            '<h5 class="modal-title text-white">' + window.escapeHtml(imageName) + '</h5>' +
            '<button type="button" class="close text-white" data-dismiss="modal">' +
            '<span>&times;</span>' +
            '</button>' +
            '</div>' +
            '<div class="modal-body text-center p-0">' +
            '<img src="' + imageUrl + '" class="img-fluid" alt="' + window.escapeHtml(imageName) + '" style="max-height: 70vh; width: auto;">' +
            '</div>' +
            '<div class="modal-footer border-0 justify-content-center">' +
            '<a href="' + imageUrl + '" class="btn btn-outline-light btn-sm" target="_blank">' +
            '<i class="fas fa-external-link-alt"></i> Open in new tab' +
            '</a>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('body').append(modalHtml);
        $('#imageModal').modal('show');

        // Remove modal from DOM when hidden
        $('#imageModal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    };

    // Auto-scroll to bottom
    function scrollToBottom(smooth = true) {
        var container = $("#messages-container");
        if (container.length === 0) return;

        var scrollTop = container[0].scrollHeight - container.height();

        if (smooth) {
            container.animate({ scrollTop: scrollTop }, 300);
        } else {
            container.scrollTop(scrollTop);
        }
    }

    // Initialize scroll if we have messages container
    if ($("#messages-container").length > 0) {
        setTimeout(function() {
            scrollToBottom(false);
        }, 100);
    }

    // Date formatting function
    function formatMessageDate(dateInput) {
        var date;
        if (!isNaN(dateInput) && dateInput > 1000000000) {
            date = new Date(parseInt(dateInput) * 1000);
        } else if (!isNaN(dateInput)) {
            date = new Date(parseInt(dateInput));
        } else {
            date = new Date(dateInput);
        }

        if (isNaN(date.getTime())) {
            return dateInput;
        }

        var month = date.getMonth() + 1;
        var day = date.getDate();
        var year = date.getFullYear().toString().substr(-2);
        var hours = date.getHours().toString().padStart(2, "0");
        var minutes = date.getMinutes().toString().padStart(2, "0");

        return month + "/" + day + "/" + year + " " + hours + ":" + minutes;
    }

    // Create message HTML
    function createMessageHtml(messageData) {
        var messageClass = messageData.is_own ? "own" : "other";
        var messageHtml = '<div class="message ' + messageClass + '" data-message-id="' + messageData.id + '">';

        messageHtml += '<div class="message-bubble">';

        // Reply information
        if (messageData.reply_to) {
            messageHtml += '<div class="reply-info">';
            messageHtml += '<div class="reply-sender">' + window.escapeHtml(messageData.reply_to.sender_name) + '</div>';
            messageHtml += '<div class="reply-text">';
            if (messageData.reply_to.has_attachments) {
                messageHtml += '<i class="fas fa-paperclip"></i> Attachment ';
            }
            if (messageData.reply_to.text) {
                messageHtml += window.escapeHtml(messageData.reply_to.text);
            }
            messageHtml += '</div></div>';
        }

        // Message text
        if (messageData.text) {
            messageHtml += '<div class="message-text" id="message-text-' + messageData.id + '">';
            messageHtml += window.escapeHtml(messageData.text).replace(/\n/g, "<br>");
            messageHtml += '</div>';
        }

        // Attachments
        if (messageData.attachments && messageData.attachments.length > 0) {
            messageHtml += '<div class="message-attachments">';
            messageData.attachments.forEach(function(attachment) {
                if (attachment.is_image) {
                    // Image preview
                    messageHtml += '<div class="attachment-image-preview">';
                    messageHtml += '<img src="' + attachment.url + '" alt="' + window.escapeHtml(attachment.original_name) + '" ';
                    messageHtml += 'class="attachment-image" onclick="openImageModal(\'' + attachment.url + '\', \'' + window.escapeHtml(attachment.original_name) + '\')">';
                    messageHtml += '<div class="attachment-image-info">';
                    messageHtml += '<div class="attachment-name">' + window.escapeHtml(attachment.original_name) + '</div>';
                    messageHtml += '<div class="attachment-size">' + attachment.file_size + '</div>';
                    messageHtml += '</div></div>';
                } else {
                    // File attachment
                    messageHtml += '<a href="' + attachment.url + '" class="attachment-item" target="_blank">';
                    messageHtml += '<div class="attachment-icon"><i class="' + attachment.icon + '"></i></div>';
                    messageHtml += '<div class="attachment-info">';
                    messageHtml += '<div class="attachment-name">' + window.escapeHtml(attachment.original_name) + '</div>';
                    messageHtml += '<div class="attachment-size">' + attachment.file_size + '</div>';
                    messageHtml += '</div></a>';
                }
            });
            messageHtml += '</div>';
        }

        // Message info
        messageHtml += '<div class="message-info">';
        if (!messageData.is_own) {
            messageHtml += '<strong>' + window.escapeHtml(messageData.sender_name) + '</strong><br>';
        }
        var formattedDate = messageData.created_at_timestamp ?
            formatMessageDate(messageData.created_at_timestamp) :
            formatMessageDate(messageData.created_at);
        messageHtml += formattedDate;
        if (messageData.is_edited) {
            messageHtml += ' <span class="message-edited">(edited)</span>';
        }
        messageHtml += '</div>';

        messageHtml += '</div>'; // Close message-bubble

        // Message actions
        messageHtml += '<div class="message-actions">';
        // Reply button
        messageHtml += '<button type="button" class="btn btn-sm btn-outline-secondary reply-btn" ' +
            'onclick="replyToMessage(' + messageData.id + ', \'' + window.escapeHtml(messageData.sender_name) + '\', ' +
            '\'' + window.escapeHtml((messageData.text || 'Attachment').substring(0, 50)) + '\', ' +
            (messageData.has_attachments ? 'true' : 'false') + ')" title="Reply">' +
            '<i class="fas fa-reply"></i></button>';

        // Edit button (only for own text messages)
        if (messageData.can_edit && messageData.text) {
            messageHtml += '<button type="button" class="btn btn-sm btn-outline-primary edit-btn" ' +
                'onclick="editMessage(' + messageData.id + ', \'' + window.escapeHtml(messageData.text) + '\')" title="Edit">' +
                '<i class="fas fa-edit"></i></button>';
        }

        // Attachments dropdown (only if has attachments)
        if (messageData.attachments && messageData.attachments.length > 0) {
            messageHtml += '<div class="dropdown">';
            messageHtml += '<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" ' +
                'data-toggle="dropdown" title="Attachments"><i class="fas fa-paperclip"></i></button>';
            messageHtml += '<div class="dropdown-menu">';
            messageHtml += '<h6 class="dropdown-header">Attachments</h6>';
            messageData.attachments.forEach(function(attachment) {
                messageHtml += '<a class="dropdown-item" href="' + attachment.url + '" target="_blank">' +
                    '<i class="' + attachment.icon + '"></i> ' + window.escapeHtml(attachment.original_name) + '</a>';
            });
            messageHtml += '</div></div>';
        }

        messageHtml += '</div>'; // Close message-actions
        messageHtml += '</div>'; // Close message

        return messageHtml;
    }

    // Handle file attachment selection - only if form exists
    if (hasChatForm) {
        $("#attachment-input").change(function() {
            var files = Array.from(this.files);
            if (files.length === 0) return;

            // Add files to attached files array
            files.forEach(function(file) {
                // Check file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    alert("File " + file.name + " is too large. Maximum size is 10MB.");
                    return;
                }

                // Check if file already attached
                var exists = attachedFiles.some(function(f) {
                    return f.name === file.name && f.size === file.size;
                });
                if (!exists) {
                    attachedFiles.push(file);
                }
            });

            updateAttachmentPreview();
            this.value = ''; // Clear input
        });
    }

    // Update attachment preview
    function updateAttachmentPreview() {
        var container = $("#attachment-preview-container");
        var list = $("#attachment-preview-list");

        if (attachedFiles.length === 0) {
            container.hide();
            return;
        }

        list.empty();
        attachedFiles.forEach(function(file, index) {
            var fileSize = formatFileSize(file.size);
            var icon = getFileIcon(file.type);

            var item = $('<div class="attachment-preview-item">' +
                '<div class="attachment-icon"><i class="' + icon + '"></i></div>' +
                '<div class="attachment-preview-info">' +
                '<div class="attachment-preview-name">' + window.escapeHtml(file.name) + '</div>' +
                '<div class="attachment-preview-size">' + fileSize + '</div>' +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger remove-attachment-btn" ' +
                'onclick="removeAttachment(' + index + ')">' +
                '<i class="fas fa-times"></i>' +
                '</button>' +
                '</div>');

            list.append(item);
        });

        container.show();
    }

    // Remove attachment
    window.removeAttachment = function(index) {
        attachedFiles.splice(index, 1);
        updateAttachmentPreview();
    };

    // Clear all attachments
    window.clearAttachments = function() {
        attachedFiles = [];
        updateAttachmentPreview();
    };

    // Format file size
    function formatFileSize(bytes) {
        var units = ['B', 'KB', 'MB', 'GB'];
        for (var i = 0; bytes > 1024 && i < units.length - 1; i++) {
            bytes /= 1024;
        }
        return Math.round(bytes * 10) / 10 + ' ' + units[i];
    }

    // Get file icon
    function getFileIcon(type) {
        if (type.startsWith('image/')) return 'fas fa-image';
        if (type === 'application/pdf') return 'fas fa-file-pdf';
        if (type.includes('word') || type.includes('document')) return 'fas fa-file-word';
        return 'fas fa-file';
    }

    // Reply to message
    window.replyToMessage = function(messageId, senderName, messageText, hasAttachments) {
        if (!hasChatForm) return;

        replyToMessageId = messageId;
        $("#reply-to-message-id").val(messageId);

        var previewText = messageText;
        if (hasAttachments) {
            previewText = '<i class="fas fa-paperclip"></i> Attachment' + (messageText ? ' • ' + previewText : '');
        }

        $("#reply-sender-name").text(senderName);
        $("#reply-preview-text").html(previewText);
        $("#reply-preview").show();
        $("#message-input").focus();
    };

    // Cancel reply
    window.cancelReply = function() {
        if (!hasChatForm) return;

        replyToMessageId = null;
        $("#reply-to-message-id").val('');
        $("#reply-preview").hide();
    };

    // Edit message
    window.editMessage = function(messageId, currentText) {
        $("#edit-message-id").val(messageId);
        $("#edit-message-text").val(currentText);
        $("#editMessageModal").modal('show');
    };

    // Save edit
    $("#save-edit-btn").click(function() {
        var messageId = $("#edit-message-id").val();
        var newText = $("#edit-message-text").val().trim();

        if (!messageId || !newText) return;

        $(this).prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: window.chatConfig.urls.editMessage || '/chat/edit-message',
            type: "POST",
            data: {
                message_id: messageId,
                message_text: newText
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Update message text in UI
                    $("#message-text-" + messageId).html(window.escapeHtml(newText).replace(/\n/g, "<br>"));

                    // Add edited indicator if not exists
                    var messageInfo = $('[data-message-id="' + messageId + '"] .message-info');
                    if (messageInfo.find('.message-edited').length === 0) {
                        messageInfo.append(' <span class="message-edited">(edited)</span>');
                    }

                    $("#editMessageModal").modal('hide');
                    showNotification("Message updated successfully", "success");
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || "Failed to update message");
            },
            complete: function() {
                $("#save-edit-btn").prop("disabled", false).html('Save Changes');
            }
        });
    });

    // Send message function
    function sendMessage() {
        if (isSending || isLoading || !selectedChatId || !hasChatForm) return false;

        var messageText = $("#message-input").val().trim();

        // Check if we have either text or attachments
        if (!messageText && attachedFiles.length === 0) {
            return false;
        }

        if (messageText.length > 1000) {
            alert("Message is too long (maximum 1000 characters)");
            return false;
        }

        isSending = true;
        $("#send-button").prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

        // Create FormData for file upload
        var formData = new FormData();
        formData.append('chat_id', selectedChatId);
        formData.append('message_text', messageText);
        formData.append('message_type', $("#message-type").val());
        if (replyToMessageId) {
            formData.append('reply_to_message_id', replyToMessageId);
        }

        // Add attachments
        attachedFiles.forEach(function(file) {
            formData.append('attachments[]', file);
        });

        $.ajax({
            url: window.chatConfig.urls.sendMessage || '/chat/send-message',
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#no-messages").remove();
                    var messageHtml = createMessageHtml(response.messageData);
                    $("#messages-list").append(messageHtml);

                    // Clear form
                    $("#message-input").val("").trigger("input");
                    clearAttachments();
                    cancelReply();

                    lastMessageId = response.messageData.id;
                    scrollToBottom();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr) {
                var errorMessage = "Failed to send message. Please try again.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            },
            complete: function() {
                isSending = false;
                $("#send-button").prop("disabled", false).html('<i class="fas fa-paper-plane"></i>');
            }
        });

        return false;
    }

    // Load more messages
    function loadMoreMessages() {
        if (isLoading || loadedMessages >= totalMessages || !selectedChatId) return;

        isLoading = true;
        $("#load-more-btn").prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        $("#loading-indicator").show();

        var currentScrollHeight = $("#messages-container")[0].scrollHeight;

        $.ajax({
            url: window.chatConfig.urls.loadMoreMessages || '/chat/load-more-messages',
            type: "GET",
            data: {
                chat_id: selectedChatId,
                offset: loadedMessages
            },
            dataType: "json",
            success: function(response) {
                if (response.success && response.messages.length > 0) {
                    var messagesHtml = "";
                    response.messages.forEach(function(messageData) {
                        messagesHtml += createMessageHtml(messageData);
                    });

                    $("#messages-list").prepend(messagesHtml);
                    loadedMessages += response.messages.length;

                    var newScrollHeight = $("#messages-container")[0].scrollHeight;
                    var scrollDiff = newScrollHeight - currentScrollHeight;
                    $("#messages-container").scrollTop(scrollDiff);

                    if (!response.hasMore) {
                        $("#load-more-btn").hide();
                    }
                } else {
                    $("#load-more-btn").hide();
                }
            },
            error: function() {
                console.log("Failed to load more messages");
            },
            complete: function() {
                isLoading = false;
                $("#load-more-btn").prop("disabled", false).html('<i class="fas fa-chevron-up"></i> Load earlier messages');
                $("#loading-indicator").hide();
            }
        });
    }

    // Character counter
    function updateCharCounter() {
        var messageInput = $("#message-input");
        if (messageInput.length === 0) {
            // Element doesn't exist - exit safely
            return;
        }

        var length = messageInput.val().length;
        var remaining = 1000 - length;
        var counter = $("#char-counter");
        var sendButton = $("#send-button");

        if (remaining < 100) {
            counter.text(remaining + " characters remaining").show();
            if (remaining < 0) {
                counter.removeClass("warning").addClass("danger");
                if (sendButton.length > 0) {
                    sendButton.prop("disabled", true);
                }
            } else if (remaining < 50) {
                counter.removeClass("danger").addClass("warning");
                if (sendButton.length > 0) {
                    sendButton.prop("disabled", false);
                }
            } else {
                counter.removeClass("danger warning");
                if (sendButton.length > 0) {
                    sendButton.prop("disabled", false);
                }
            }
        } else {
            counter.hide();
            if (sendButton.length > 0) {
                sendButton.prop("disabled", false);
            }
        }
    }

    // Show notification
    function showNotification(message, type) {
        var alertClass = type === "success" ? "alert-success" : "alert-danger";
        var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;">' +
            '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
            message + '</div>');

        $("body").append(notification);

        setTimeout(function() {
            notification.alert("close");
        }, 3000);
    }

    // SEARCH FUNCTIONALITY

    // Toggle search container
    $('.search-toggle-btn').click(function() {
        var container = $('.search-container');
        var isVisible = container.is(':visible');

        if (isVisible) {
            container.slideUp(200, function() {
                // Show search toggle button again when hiding search
                $('.search-toggle-btn').show();
            });
            if (isSearchMode) {
                cancelSearch();
            }
        } else {
            container.slideDown(200, function() {
                // Hide search toggle button when showing search
                $('.search-toggle-btn').hide();
                $('.search-input').focus();
            });
        }
    });

    // Search when typing in mobile and desktop
    $('.search-input').on('input', function() {
        var query = $(this).val().trim();
        if (query.length >= 2) {
            performSearch(query);
        } else if (query.length === 0 && isSearchMode) {
            cancelSearch();
        }
    });

    // Search button click
    $('.search-btn').click(function() {
        var query = $('.search-input').val().trim();
        if (query.length >= 2) {
            performSearch(query);
        } else if (query.length === 0) {
            alert('Please enter search text');
        } else {
            alert('Please enter at least 2 characters');
        }
    });

    // Search on Enter key
    $('.search-input').keypress(function(e) {
        if (e.which === 13) {
            var query = $(this).val().trim();
            if (query.length >= 2) {
                performSearch(query);
            }
        }
    });

    // Cancel search button
    $('.clear-search-btn').click(function() {
        if (isSearchMode) {
            cancelSearch();
        } else {
            $('.search-input').val('');
            // Hide search container and show search toggle when clearing empty search
            $('.search-container').slideUp(200, function() {
                $('.search-toggle-btn').show();
            });
        }
    });

    // Perform search
    function performSearch(query) {
        // Save original messages if first search
        if (!isSearchMode) {
            originalMessages = $('#messages-list').html();
        }

        var searchUrl = window.chatConfig.urls.searchMessages || '/chat/search-messages';
        var searchData = { q: query, mode: 'full' };

        // Add chat_id for employee chats
        if (window.chatConfig.selectedChatId) {
            searchData.chat_id = window.chatConfig.selectedChatId;
        }

        // Show loading
        $('#messages-list').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');

        $.ajax({
            url: searchUrl,
            type: 'GET',
            data: searchData,
            dataType: 'json',
            success: function(response) {
                if (response.success && response.results.length > 0) {
                    displaySearchMessages(response.results, query);
                    enterSearchMode();
                } else {
                    showNoSearchResults(query);
                }
            },
            error: function() {
                $('#messages-list').html('<div class="alert alert-danger">Search failed. Please try again.</div>');
            }
        });
    }

    // Display search results as messages
    function displaySearchMessages(results, query) {
        var html = '';

        results.forEach(function(result) {
            var highlightedText = highlightSearchTerms(result.text, query);
            var messageClass = result.is_own ? 'own' : 'other';

            html += '<div class="message ' + messageClass + '" data-message-id="' + result.id + '">';
            html += '<div class="message-bubble">';
            html += '<div class="message-text">' + highlightedText + '</div>';
            html += '<div class="message-info">';
            if (!result.is_own) {
                html += '<strong>' + window.escapeHtml(result.sender_name) + '</strong><br>';
            }
            html += result.created_at;
            if (result.has_attachments) {
                html += ' <i class="fas fa-paperclip" title="Has attachments"></i>';
            }
            html += '</div>';
            html += '</div></div>';
        });

        $('#messages-list').html(html);
        scrollToBottom(false);
    }

    // Show no results message
    function showNoSearchResults(query) {
        var html = '<div class="no-search-results">';
        html += '<i class="fas fa-search"></i>';
        html += '<h5>No messages found</h5>';
        html += '<p>No messages containing "<strong>' + window.escapeHtml(query) + '</strong>" were found in this chat.</p>';
        html += '</div>';

        $('#messages-list').html(html);
    }

    // Enter search mode
    function enterSearchMode() {
        isSearchMode = true;
        $('.chat-header').addClass('search-mode');
        $('.clear-search-btn').html('<i class="fas fa-times"></i>').attr('title', 'Cancel search');

        // Hide load more button
        $('#load-more-btn').hide();
    }

    // Cancel search and restore original messages
    function cancelSearch() {
        if (originalMessages) {
            $('#messages-list').html(originalMessages);
            scrollToBottom(false);
        }

        isSearchMode = false;
        $('.chat-header').removeClass('search-mode');
        $('.search-input').val('');
        $('.search-container').slideUp(200);
        $('.clear-search-btn').html('<i class="fas fa-times"></i>').attr('title', 'Clear');

        // Show load more button if needed
        if (loadedMessages < totalMessages) {
            $('#load-more-btn').show();
        }

        originalMessages = null;
    }

    // Highlight search terms
    function highlightSearchTerms(text, query) {
        var escapedText = window.escapeHtml(text);
        var escapedQuery = window.escapeHtml(query);
        var regex = new RegExp('(' + escapedQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escapedText.replace(regex, '<span class="search-highlight">$1</span>');
    }

    // Event handlers - check element existence before binding
    $("#load-more-btn").on('click', loadMoreMessages);

    if ($("#send-button").length > 0) {
        $("#send-button").click(function(e) {
            e.preventDefault();
            sendMessage();
        });
    }

    if ($("#message-input").length > 0) {
        $("#message-input").keydown(function(e) {
            if (e.ctrlKey && e.keyCode === 13) {
                e.preventDefault();
                sendMessage();
            }
        }).on("input", function() {
            this.style.height = "auto";
            this.style.height = Math.min(this.scrollHeight, 100) + "px";
            updateCharCounter();
        });
    }

    // Initialize - only if we have chat form
    if (hasChatForm) {
        updateCharCounter();
    }

    /*
    function toggleParticipants(chatId) {
        var dropdown = $("#participants-" + chatId);
        var toggle = $(".participants-toggle");
        var icon = $("#expand-icon-" + chatId);
        var isMobile = window.innerWidth <= 768;

        if (dropdown.is(":visible")) {
            // Hide dropdown
            dropdown.slideUp(200);
            icon.removeClass("expanded");
        } else {
            // Close other dropdowns first
            $(".participants-dropdown").slideUp(200);
            $(".expand-icon").removeClass("expanded");

            // Calculate position based on toggle button for BOTH desktop and mobile
            var toggleOffset = toggle.offset();
            var toggleHeight = toggle.outerHeight();
            var toggleWidth = toggle.outerWidth();
            var chatContent = $('.chat-content');
            var chatContentOffset = chatContent.offset();

            // Position dropdown relative to chat-content container
            var relativeTop = toggleOffset.top - chatContentOffset.top + toggleHeight + 8;

            if (isMobile) {
                // Mobile positioning
                var windowWidth = $(window).width();
                if (windowWidth <= 480) {
                    dropdown.css({
                        'position': 'absolute',
                        'top': relativeTop + 'px',
                        'left': '5px',
                        'right': '5px',
                        'width': 'auto',
                        'max-width': 'none'
                    });
                } else {
                    dropdown.css({
                        'position': 'absolute',
                        'top': relativeTop + 'px',
                        'right': '10px',
                        'left': 'auto',
                        'width': '90vw',
                        'max-width': '320px'
                    });
                }

                // Show dropdown with fade effect
                dropdown.fadeIn(300);
            } else {
                // Desktop positioning - position relative to toggle button
                var relativeLeft = toggleOffset.left - chatContentOffset.left + toggleWidth - 320; // 320px = dropdown width

                // Ensure dropdown doesn't go off-screen
                var maxLeft = $(window).width() - 320 - 20; // 20px margin
                if (relativeLeft > maxLeft) {
                    relativeLeft = maxLeft - chatContentOffset.left;
                }

                // Ensure dropdown doesn't go too far left
                if (relativeLeft < 10) {
                    relativeLeft = 10;
                }

                dropdown.css({
                    'position': 'absolute',
                    'top': relativeTop + 'px',
                    'left': relativeLeft + 'px',
                    'right': 'auto',
                    'width': '320px',
                    'max-width': '320px'
                });

                // Show dropdown with slide effect
                dropdown.slideDown(200);
            }

            icon.addClass("expanded");
        }
    }

    // Close participants dropdown when clicking outside
    $(document).on('click', function(e) {
        // Check if click is outside participants area and dropdown area
        if (!$(e.target).closest('.chat-participants').length &&
            !$(e.target).closest('.participants-dropdown').length) {

            $('.participants-dropdown').slideUp(200);
            $('.expand-icon').removeClass('expanded');
        }
    });

    // Handle window resize for mobile/desktop switching and dropdown repositioning
    $(window).resize(function() {
        var isMobile = window.innerWidth <= 768;

        // Reset dropdown positioning on resize if visible
        $('.participants-dropdown').each(function() {
            if ($(this).is(':visible')) {
                var dropdown = $(this);
                var chatId = dropdown.attr('id').replace('participants-', '');

                // Re-apply positioning logic by toggling twice
                toggleParticipants(chatId);
                toggleParticipants(chatId);
            }
        });
    });

    function removeParticipant(chatId, userId, userName) {
        if (!confirm("Remove " + userName + " from this chat?")) return;

        $.ajax({
            url: window.chatConfig.urls.removeParticipant || '/chat/remove-participant',
            type: "POST",
            data: { chat_id: chatId, user_id: userId },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $('[data-user-id="' + userId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        var dropdown = $("#participants-" + chatId);
                        var remainingCount = dropdown.find('.participant-item').length;
                        dropdown.closest('.chat-participants').find('.participants-count')
                            .text(remainingCount + ' participant' + (remainingCount == 1 ? '' : 's'));
                    });
                    showNotification("Participant removed successfully", "success");
                } else {
                    showNotification("Error: " + response.message, "error");
                }
            },
            error: function(xhr) {
                showNotification(xhr.responseJSON?.message || "Failed to remove participant", "error");
            }
        });
    }

    function showAddParticipantModal(chatId) {
        // Close dropdown first
        $("#participants-" + chatId).slideUp(200);
        $("#expand-icon-" + chatId).removeClass("expanded");

        // Show modal
        $("#addParticipantChatId").val(chatId);
        $("#addParticipantModal").modal("show");
    }

    // Make functions globally accessible
    window.toggleParticipants = toggleParticipants;
    window.removeParticipant = removeParticipant;
    window.showAddParticipantModal = showAddParticipantModal;
     */

    window.showNotification = showNotification;
    window.createMessageHtml = createMessageHtml;

    /*
    // Initialize dropdown positioning on document ready
    $(document).ready(function() {
        // Ensure dropdowns are properly positioned when page loads
        $('.participants-dropdown').each(function() {
            var dropdown = $(this);
            dropdown.css({
                'position': 'absolute',
                'display': 'none',
                'z-index': '1500'
            });
        });
    });*/
});