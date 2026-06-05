/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: OCP */
/** global: OC */
'use strict';

var APP_ID = 'sharereview';

 /* @namespace OCA.ShareReview.Notification
 */
OCA.ShareReview.Notification = {
    draggedItem: null,

    info: function (header, text, guidance) {
        document.body.insertAdjacentHTML('beforeend',
            '<div id="sharereviewDialogOverlay" class="shareReviewDialogDim"></div>'
            + '<div id="shareReviewDialogContainer" class="shareReviewDialog">'
            + '<a class="shareReviewDialogClose" id="shareReviewDialogBtnClose"></a>'
            + '<div class="shareReviewDialogHeader"><span class="shareReviewDialogHeaderIcon"></span><span id="shareReviewDialogHeader" style="margin-left: 10px;"></span></div>'
            + '<span id="shareReviewDialogGuidance" class="userGuidance"></span><br><br>'
            + '<div id="shareReviewDialogContent">'
            + '</div>'
            + '<br><div class="shareReviewDialogButtonrow">'
            + '<a class="button primary" id="shareReviewDialogBtnGo">' + t(APP_ID, 'OK') + '</a>'
            + '</div></div>'
        );
        document.getElementById('shareReviewDialogHeader').textContent = header;
        document.getElementById('shareReviewDialogGuidance').innerHTML = guidance;
        document.getElementById('shareReviewDialogContent').innerHTML = text;
        document.getElementById("shareReviewDialogBtnClose").addEventListener("click", OCA.ShareReview.Notification.dialogClose);
        document.getElementById("shareReviewDialogBtnGo").addEventListener("click", OCA.ShareReview.Notification.dialogClose);
    },

    confirm: function (header, text, callback) {
        document.body.insertAdjacentHTML('beforeend',
            '<div id="shareReviewDialogOverlay" class="shareReviewDialogDim"></div>'
            + '<div id="shareReviewDialogContainer" class="shareReviewDialog">'
            + '<a class="shareReviewDialogClose" id="shareReviewDialogBtnClose"></a>'
            + '<div class="shareReviewDialogHeader"><span class="shareReviewDialogHeaderIcon"></span><span id="shareReviewDialogHeader" style="margin-left: 10px;"></span></div>'
            + '<div id="shareReviewDialogContent">'
            + '<div style="text-align:center; padding-top:100px" class="get-metadata icon-loading"></div>'
            + '</div>'
            + '<br><div class="shareReviewDialogButtonrow">'
            + '<a class="button" id="shareReviewDialogBtnCancel">' + t(APP_ID, 'Cancel') + '</a>'
            + '<a class="button primary" id="shareReviewDialogBtnGo">' + t(APP_ID, 'OK') + '</a>'
            + '</div></div>'
        );
        document.getElementById('shareReviewDialogHeader').textContent = header;
        document.getElementById('shareReviewDialogContent').innerHTML = text;
        document.getElementById("shareReviewDialogBtnClose").addEventListener("click", OCA.ShareReview.Notification.dialogClose);
        document.getElementById("shareReviewDialogBtnCancel").addEventListener("click", OCA.ShareReview.Notification.dialogClose);
        document.getElementById("shareReviewDialogBtnGo").addEventListener("click", callback);
    },

    /**
     * Function to display notifications.
     * @param {('info'|'success'|'error')} type - The type of the notification.
     * @param {string} message - The notification message.
     */
    notification: function (type, message) {
        if (parseInt(OC.config.versionstring.substr(0, 2)) >= 17) {
            if (type === 'success') {
                OCP.Toast.success(message)
            } else if (type === 'error') {
                OCP.Toast.error(message)
            } else {
                OCP.Toast.info(message)
            }
        } else {
            OC.Notification.showTemporary(message);
        }
    },

    /**
     * @param {string} header Popup header as text
     * @param callback Callback function of the OK button
     */
    htmlDialogInitiate: function (header, callback) {
        document.body.insertAdjacentHTML('beforeend',
            '<div id="shareReviewDialogOverlay" class="shareReviewDialogDim"></div>'
            + '<div id="shareReviewDialogContainer" class="shareReviewDialog">'
            + '<a class="shareReviewDialogClose" id="shareReviewDialogBtnClose"></a>'
            + '<div class="shareReviewDialogHeader"><span class="shareReviewDialogHeaderIcon"></span><span id="shareReviewDialogHeader" style="margin-left: 10px;"></span></div>'
            + '<span id="shareReviewDialogGuidance" class="userGuidance"></span><br><br>'
            + '<div id="shareReviewDialogContent">'
            + '<div style="text-align:center; padding-top:100px" class="get-metadata icon-loading"></div>'
            + '</div>'
            + '<br><div class="shareReviewDialogButtonrow">'
            + '<a class="button" id="shareReviewDialogBtnCancel">' + t(APP_ID, 'Cancel') + '</a>'
            + '<a class="button primary" id="shareReviewDialogBtnGo">' + t(APP_ID, 'OK') + '</a>'
            + '</div></div>'
        );

        document.getElementById('shareReviewDialogHeader').textContent = header;
        document.getElementById("shareReviewDialogBtnClose").addEventListener("click", OCA.ShareReview.Notification.dialogClose);
        document.getElementById("shareReviewDialogBtnCancel").addEventListener("click", OCA.ShareReview.Notification.dialogClose);
        document.getElementById("shareReviewDialogBtnGo").addEventListener("click", callback);
    },

    htmlDialogUpdate: function (content, guidance) {
        document.getElementById('shareReviewDialogContent').innerHTML = '';
        document.getElementById('shareReviewDialogContent').appendChild(content);
        document.getElementById('shareReviewDialogGuidance').innerHTML = guidance;
    },

    htmlDialogUpdateAdd: function (guidance) {
        document.getElementById('shareReviewDialogGuidance').innerHTML += '<br>' + guidance;
    },

    dialogClose: function () {
        document.getElementById('shareReviewDialogContainer').remove();
        document.getElementById('shareReviewDialogOverlay').remove();
    },
}