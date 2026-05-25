/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: OCP */
/** global: OC */
/** global: table */
/** global: Headers */

'use strict';

/**
 * @namespace OCA.ShareReview.Visualization
 */
OCA.ShareReview.Visualization = {

    // *************
    // *** table ***
    // *************

    buildDataTable: function (data) {

        let domTarget = document.getElementById("dataTable");
        domTarget.innerHTML = '';
        if (OCA.ShareReview.tableObject) {
            OCA.ShareReview.tableObject.destroy();
            OCA.ShareReview.tableObject = null;
        }

        this.hideElement('loadingContainer');

        if (data.length === 0) {
            this.showElement('noDataContainer');
            this.hideElement('tableContainer');
            this.hideElement('notSecuredContainer');

            return;
        } else {
            this.hideElement('noDataContainer');
            this.hideElement('notSecuredContainer');
            this.showElement('tableContainer');
        }

        let language = {
            // TRANSLATORS Noun
            search: t('sharereview', 'Search'),
            lengthMenu: t('sharereview', 'Show _MENU_ entries'),
            info: t('sharereview', 'Showing _START_ to _END_ of _TOTAL_ entries'),
            infoEmpty: t('sharereview', 'Showing 0 to 0 of 0 entries'),
            paginate: {
                first: '<<',
                previous: '<',
                next: '>',
                last: '>>'
            },
        };

        let columnTitles = {
            app: t('sharereview', 'App'),
            object: t('sharereview', 'Item'),
            initiator: t('sharereview', 'Initiator'),
            type: t('sharereview', 'Type'),
            permissions: t('sharereview', 'Permissions'),
            time: t('sharereview', 'Time'),
            action: t('sharereview', 'Action'),
        };

        let columns = Object.keys(data[0]).map(key => ({
            title: columnTitles[key] ?? key,
            data: key,
        }));

        columns = OCA.ShareReview.Visualization.addColumnRender(columns);
        columns.unshift({
            title: '<input type="checkbox" id="selectAllShares" title="' + t('sharereview', 'Select all') + '">',
            data: 'action',
            orderable: false,
            render: OCA.ShareReview.Visualization.renderSelect
        });
        const timeIndex = columns.findIndex(c => c.data === 'time');
        const isDataLengthGreaterThanDefault = data.length > 10;

        OCA.ShareReview.tableObject = new DataTable(domTarget, {
            pagingType: 'simple_numbers',
            autoWidth: false,
            data: data,
            columns: columns,
            language: language,
            order: [[timeIndex, 'desc']],
            layout: {
                topStart: isDataLengthGreaterThanDefault ? 'pageLength' : null,
                topEnd: isDataLengthGreaterThanDefault ? 'search' : null,
                bottomStart: isDataLengthGreaterThanDefault ? 'info' : null,
                bottomEnd: isDataLengthGreaterThanDefault ? 'paging' : null,
            },
        });

        // Reattach the checkbox listeners whenever the table is redrawn
        OCA.ShareReview.tableObject.on('draw', OCA.ShareReview.UI.initCheckboxListeners);

        let headerCheckbox = document.getElementById('selectAllShares');
        if (headerCheckbox) {
            headerCheckbox.addEventListener('change', OCA.ShareReview.UI.handleSelectAll);
        }
        OCA.ShareReview.UI.initCheckboxListeners();
    },

    addColumnRender: function (columns) {
        columns.forEach(obj => {
            if (obj.data === 'permissions') {
                obj.render = OCA.ShareReview.Visualization.renderPermissions;
            } else if (obj.data === 'time') {
                obj.render = OCA.ShareReview.Visualization.renderDates;
            } else if (obj.data === 'action') {
                obj.render = OCA.ShareReview.Visualization.renderAction;
            } else if (obj.data === 'type') {
                obj.render = OCA.ShareReview.Visualization.renderTypes;
            } else if (obj.data === 'object') {
                obj.render = OCA.ShareReview.Visualization.renderObject;
            }
        });
        return columns;
    },

    renderPermissions: function (data) {
        let iconClass = 'icon-sharereview-more';
        let titleText = t('sharereview', 'more')
        let dataArray = data.split(';');

        switch (parseInt(dataArray[0])) {
            case 1:
            case 17:
                iconClass = 'icon-sharereview-read';
                titleText = t('sharereview', 'read');
                break;
            case 2:
            case 5:
            case 31:
            case 15:
            case 19:
                iconClass = 'icon-sharereview-edit';
                titleText = t('sharereview', 'edit');
                break;
        }

        let returnString = '<div style="display: flex;">' +
            '<div permission="' + dataArray[0] + '" class="' + iconClass + '" title="' + titleText + '"></div>';

        if (dataArray[1] !== '') {
            returnString += '&nbsp;<div class="icon-sharereview-password" title="' + t('sharereview', 'Password protected') + '"></div>';
        }
        if (dataArray[2] !== '') {
            returnString += '&nbsp;<div class="icon-sharereview-calendar" title="' + t('sharereview', 'Expiration date: ')  + dataArray[2] + '"></div>';
        }
        returnString += '</div>';
        return returnString;
    },

    renderObject: function (data) {
        let dataArray = data.split(';');
        if (dataArray.length !== 1) {
            return '<span title="' + dataArray[0] + '">' + dataArray[1] + '</span>';
        } else {
            return '<span title="' + data + '">' + data + '</span>';
        }
    },

    renderTypes: function (data) {
        let iconClass = 'icon-sharereview-link';
        let titleText = 'more'
        let dataArray = data.split(';');

        switch (parseInt(dataArray[0])) {
            case 12:
                iconClass = 'icon-sharereview-deck';
                titleText = t('sharereview', 'Deck');
                break;
            case 10:
                iconClass = 'icon-sharereview-talk';
                titleText = t('sharereview', 'Talk room');
                break;
            case 7:
                iconClass = 'icon-sharereview-team';
                titleText = t('sharereview', 'Team');
                break;
            case 9: // remote group
            case 6:
                iconClass = 'icon-sharereview-federation';
                titleText = t('sharereview', 'Federation');
                break;
            case 4:
                iconClass = 'icon-sharereview-email';
                titleText = t('sharereview', 'E-mail');
                break;
            case 3:
                iconClass = 'icon-sharereview-link';
                titleText = t('sharereview', 'Link');
                break;
            case 1:
                iconClass = 'icon-sharereview-group';
                titleText = t('sharereview', 'User group');
                break;
            case 0:
                iconClass = 'icon-sharereview-user';
                titleText = t('sharereview', 'User');
                break;
        }

        return '<div data-order="' + parseInt(dataArray[0]) + '" style="display:flex;">' +
            '<div class="' + iconClass + '" title="' + titleText + '"></div>' +
            '<span style="margin-left: 10px;">' + dataArray[1] + '</span>' +
            '</div>';
    },

    renderDates: function (data, type) {
        const parsedTimestamp = Number(data);
        const date = Number.isNaN(parsedTimestamp) ? new Date(data) : new Date(parsedTimestamp);

        if (Number.isNaN(date.getTime())) {
            return data;
        }

        const timeValue = date.getTime();

        if (type === 'sort' || type === 'type') {
            return timeValue;
        }

        return '<span data-order="' + timeValue + '">' + date.toLocaleString() + '</span>';
    },

    renderAction: function (data) {
        if (data !== '') {
            let div = document.createElement('div');
            div.classList.add('icon-sharereview-delete');
            div.id = data;
            div.addEventListener('click', OCA.ShareReview.UI.handleDeleteClicked);
            return div;
        }
        return null;
    },

    renderSelect: function (data) {
        return '<input type="checkbox" class="share-selection" value="' + data + '">';
    },

    showElement: function (element) {
        if (document.getElementById(element)) {
            document.getElementById(element).hidden = false;
            //document.getElementById(element).style.removeProperty('display');
        }
    },

    hideElement: function (element) {
        if (document.getElementById(element)) {
            document.getElementById(element).hidden = true;
            //document.getElementById(element).style.display = 'none';
        }
    },
}
