/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: OCP */
/** global: table */

'use strict';

OCA.ShareReview = Object.assign({}, OCA.ShareReview, {
    initialDocumentTitle: null,
    currentReportData: {},
    tableObject: null,

    headers: function () {
        let headers = new Headers();
        headers.append('requesttoken', OC.requestToken);
        headers.append('OCS-APIREQUEST', 'true');
        headers.append('Content-Type', 'application/json');
        return headers;
    }
});

OCA.ShareReview.UI = {
    handleDeleteClicked: function (evt) {
        let shareId = evt.target.id;
        OCA.ShareReview.Notification.confirm(
            t('sharereview', 'Delete'),
            t('sharereview', 'Are you sure?') + ' ' + t('sharereview', 'The share will be deleted!'),
            function () {
                OCA.ShareReview.Backend.delete(shareId);
            }
        );
    },

    handleSelectAll: function () {
        let checkboxes = document.querySelectorAll('#dataTable .share-selection');
        let headerCheckbox = document.getElementById('selectAllShares');
        let allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => { cb.checked = !allChecked; });
        if (headerCheckbox) headerCheckbox.checked = !allChecked;
        OCA.ShareReview.UI.updateDeleteButtonVisibility();
    },

    updateDeleteButtonVisibility: function () {
        let checkboxes = document.querySelectorAll('#dataTable .share-selection');
        let checkedCount = document.querySelectorAll('#dataTable .share-selection:checked').length;
        let deleteBtn = document.getElementById('deleteSelectedShares');
        if (deleteBtn) deleteBtn.hidden = checkedCount <= 1;
        let headerCheckbox = document.getElementById('selectAllShares');
        if (headerCheckbox) headerCheckbox.checked = (checkboxes.length > 0 && checkedCount === checkboxes.length);
    },

    initCheckboxListeners: function () {
        let checkboxes = document.querySelectorAll('#dataTable .share-selection');
        checkboxes.forEach(cb => cb.addEventListener('change', OCA.ShareReview.UI.updateDeleteButtonVisibility));
        OCA.ShareReview.UI.updateDeleteButtonVisibility();
    },

    handleDeleteSelected: function () {
        let checkboxes = document.querySelectorAll('#dataTable .share-selection:checked');
        if (checkboxes.length === 0) return;
        let ids = Array.from(checkboxes).map(cb => cb.value);
        OCA.ShareReview.Notification.confirm(
            t('sharereview', 'Delete'),
            t('sharereview', 'Are you sure?') + ' ' + t('sharereview', 'The share will be deleted!'),
            function () {
                OCA.ShareReview.Backend.deleteMultiple(ids);
            }
        );
    },

    initBulkActions: function () {
        let deleteBtn = document.getElementById('deleteSelectedShares');
        if (deleteBtn) deleteBtn.addEventListener('click', OCA.ShareReview.UI.handleDeleteSelected);
        OCA.ShareReview.UI.updateDeleteButtonVisibility();
    },
};

OCA.ShareReview.Navigation = {
    buildNavigation: function (data) {
        document.getElementById('shareReviewNavigation').innerHTML = '';
        let reviewTimestamp = OCA.ShareReview.Navigation.getInitialState('reviewTimestamp');
        let showTalk = OCA.ShareReview.Navigation.getInitialState('showTalk') === "true";
        let localTime = '';
        if (reviewTimestamp !== '0' && reviewTimestamp !== 0) {
            let timestampInMilliseconds = reviewTimestamp * 1000;
            let date = new Date(timestampInMilliseconds);
            localTime = date.toLocaleString();
        }

        let container = document.getElementById('shareReviewNavigation');
        let navigations = [
            {
                id: 'navAllShares',
                name: t('sharereview', 'All Shares'),
                event: OCA.ShareReview.Navigation.handleAllNavigation,
                style: 'icon-sharereview-shares',
                pinned: false
            },
            {
                id: 'navNewShares',
                name: t('sharereview', 'New Shares'),
                event: OCA.ShareReview.Navigation.handleNewSharesNavigation,
                style: 'icon-sharereview-new',
                pinned: false
            },
            {
                id: 'navConfirm',
                name: t('sharereview', 'Confirm reviewed'),
                event: OCA.ShareReview.Navigation.handleConfirmNavigation,
                style: 'icon-sharereview-check',
                pinned: false
            },
            {
                id: 'navReset',
                name: t('sharereview', 'Reset time'),
                event: OCA.ShareReview.Navigation.handleConfirmResetNavigation,
                style: 'icon-sharereview-reset',
                pinned: false
            }
        ];
        for (let navigation of navigations) {
            container.appendChild(OCA.ShareReview.Navigation.buildNavigationRow(navigation));
        }
        if (localTime !== '') {
            container.appendChild(OCA.ShareReview.Navigation.buildNavigationRow({
                id: 'navTime',
                name: localTime,
                event: false,
                style: 'icon-sharereview-time',
                pinned: false
            }));
        }
        let spacer = document.createElement('li');
        spacer.id = 'navSpacer';
        container.appendChild(spacer);
        container.appendChild(OCA.ShareReview.Navigation.buildNavigationRow({
            id: 'navExport',
            name: t('sharereview', 'Export report'),
            event: OCA.ShareReview.Navigation.handleExportNavigation,
            style: 'icon-sharereview-download',
            pinned: false
        }));

        let liTalk = document.createElement('li');
        liTalk.classList.add('pinned', 'first-pinned');
        let checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = 'showTalkShares';
        checkbox.checked = showTalk;
        checkbox.classList.add('checkbox');
        checkbox.addEventListener('change', OCA.ShareReview.Navigation.handleShowTalkChange);
        let label = document.createElement('label');
        label.setAttribute('for', 'showTalkShares');
        label.innerText = t('sharereview', 'Show talk shares');
        liTalk.appendChild(checkbox);
        liTalk.appendChild(label);
        container.appendChild(liTalk);
    },

    buildNavigationRow: function (data) {
        let li = document.createElement('li');
        li.id = data['id'];
        if (data['pinned']) li.classList.add('pinned', 'first-pinned');
        let a = document.createElement('a');
        data['style'] ? a.classList.add(data['style'], 'svg'): false;
        data['event'] ? a.addEventListener('click', data['event']) : false;
        a.innerText = data['name'];
        li.appendChild(a);
        return li;
    },

    handleAllNavigation: function () {
        document.getElementById('navAllShares').classList.add('active');
        document.getElementById('navNewShares').classList.remove('active');
        document.getElementById('navExport').classList.remove('active');
        OCA.ShareReview.Backend.getData();
        OCA.ShareReview.Visualization.hideElement('exportContainer');
    },

    handleNewSharesNavigation: function () {
        document.getElementById('navNewShares').classList.add('active');
        document.getElementById('navAllShares').classList.remove('active');
        document.getElementById('navExport').classList.remove('active');
        OCA.ShareReview.Backend.getData(true);
        OCA.ShareReview.Visualization.hideElement('exportContainer');
    },

    handleConfirmNavigation: function () {
        OCA.ShareReview.Backend.confirm();
    },

    handleConfirmResetNavigation: function () {
        OCA.ShareReview.Backend.confirmReset();
    },

    handleExportNavigation: function () {
        document.getElementById('navNewShares').classList.remove('active');
        document.getElementById('navAllShares').classList.remove('active');
        document.getElementById('navExport').classList.add('active');
        OCA.ShareReview.Visualization.hideElement('tableContainer');
        OCA.ShareReview.Visualization.hideElement('noDataContainer');
        OCA.ShareReview.Visualization.hideElement('notSecuredContainer');
        OCA.ShareReview.Visualization.hideElement('loadingContainer');
        OCA.ShareReview.Visualization.showElement('exportContainer');
        document.getElementById('defaultFolder').value = OCA.ShareReview.Navigation.getInitialState('reportFolder') || '';
        document.getElementById('scheduleSelect').value = OCA.ShareReview.Navigation.getInitialState('schedule') || 'none';
        document.getElementById('typeSelect').value = OCA.ShareReview.Navigation.getInitialState('reportType') || 'pdf';
        document.getElementById('folderOwner').value = OCA.ShareReview.Navigation.getInitialState('folderOwner') || '';
    },

    handleExportClick: function (type) {
        OC.dialogs.filepicker(t('sharereview', 'Select folder'), function (path) {
            if (path) {
                OCA.ShareReview.Backend.export(path, type);
            }
        }, false, 'httpd/unix-directory', true, 1);
    },

    handleShowTalkChange: function () {
        let state = document.getElementById('showTalkShares').checked.toString();
        OCA.ShareReview.Backend.showTalk(state);
    },

    getInitialState: function (key) {
        const app = 'sharereview';
        const elem = document.querySelector('#initial-state-' + app + '-' + key);
        if (elem === null) {
            return false;
        }
        return JSON.parse(atob(elem.value))
    },

}

OCA.ShareReview.Backend = {
    getData: function (onlyNew) {
        let newUrl = '';
        if (onlyNew) newUrl = '/new';
        let requestUrl = OC.generateUrl('apps/sharereview/data') + newUrl;
        OCA.ShareReview.Visualization.showElement('loadingContainer');
        OCA.ShareReview.Visualization.hideElement('noDataContainer');
        OCA.ShareReview.Visualization.hideElement('tableContainer');
        OCA.ShareReview.Visualization.hideElement('notSecuredContainer');
        OCA.ShareReview.Visualization.hideElement('exportContainer');
        fetch(requestUrl, {
            method: 'GET',
            headers: OCA.ShareReview.headers()
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            })
            .then(data => {
                OCA.ShareReview.Visualization.buildDataTable(data);
                OCA.ShareReview.Visualization.hideElement('loadingContainer');
            })
            .catch(error => {
                OCA.ShareReview.Notification.notification('error', error)
                OCA.ShareReview.Visualization.hideElement('loadingContainer');
                OCA.ShareReview.Visualization.hideElement('noDataContainer');
                OCA.ShareReview.Visualization.showElement('notSecuredContainer');
                OCA.ShareReview.Visualization.hideElement('tableContainer');
            });
     },

    delete: function (shareId) {
        OCA.ShareReview.Notification.dialogClose();
        let requestUrl = OC.generateUrl('apps/sharereview/delete/') + shareId;
        fetch(requestUrl, {
            method: 'DELETE',
            headers: OCA.ShareReview.headers()
        })
            .then(response => response.json())
            .then(data => {
                if (document.getElementById('pauseUpdate').checked === false) {
                    OCA.ShareReview.Backend.getData();
                    OCA.ShareReview.Notification.notification('success', t('sharereview', 'Share deleted'));
                } else {
                    OCA.ShareReview.Notification.notification('success', t('sharereview', 'Share deleted') + '. ' + t('sharereview', 'Table not reloaded'));
                }
            })
            .catch(error => {
                OCA.ShareReview.Notification.notification('error', t('sharereview', 'Request could not be processed'))
            });
    },

    deleteMultiple: function (shareIds) {
        OCA.ShareReview.Notification.dialogClose();
        let promises = shareIds.map(id => {
            let requestUrl = OC.generateUrl('apps/sharereview/delete/') + id;
            return fetch(requestUrl, {
                method: 'DELETE',
                headers: OCA.ShareReview.headers()
            });
        });
        Promise.all(promises)
            .then(() => {
                if (document.getElementById('pauseUpdate').checked === false) {
                    OCA.ShareReview.Backend.getData();
                    OCA.ShareReview.Notification.notification('success', t('sharereview', 'Share deleted'));
                } else {
                    OCA.ShareReview.Notification.notification('success', t('sharereview', 'Share deleted') + '. ' + t('sharereview', 'Table not reloaded'));
                }
            })
            .catch(() => {
                OCA.ShareReview.Notification.notification('error', t('sharereview', 'Request could not be processed'));
            });
    },

    confirm: function () {
        let requestUrl = OC.generateUrl('apps/sharereview/confirm');
        fetch(requestUrl, {
            method: 'POST',
            headers: OCA.ShareReview.headers()
        })
            .then(response => response.json())
            .then(data => {
                OCA.ShareReview.Notification.notification('success', t('sharereview', 'Timestamp saved'));
                let timestampInMilliseconds = data * 1000;
                let date = new Date(timestampInMilliseconds);
                let navTime = document.getElementById('navTime');
                if (!navTime) {
                    let container = document.getElementById('shareReviewNavigation');
                    let spacer = document.getElementById('navSpacer');
                    navTime = OCA.ShareReview.Navigation.buildNavigationRow({
                        id: 'navTime',
                        name: date.toLocaleString(),
                        event: false,
                        style: 'icon-sharereview-time',
                        pinned: false
                    });
                    container.insertBefore(navTime, spacer);
                } else {
                    navTime.firstChild.innerText = date.toLocaleString();
                }
            });
    },

    confirmReset: function () {
        let requestUrl = OC.generateUrl('apps/sharereview/confirmReset');
        fetch(requestUrl, {
            method: 'POST',
            headers: OCA.ShareReview.headers()
        })
            .then(response => response.json())
            .then(data => {
                OCA.ShareReview.Notification.notification('success', t('sharereview', 'Timestamp deleted'));
                let navTime = document.getElementById('navTime');
                if (navTime) navTime.remove();
            });
    },

    showTalk: function(state) {
        let requestUrl = OC.generateUrl('apps/sharereview/showTalk');
        fetch(requestUrl, {
            method: 'POST',
            headers: OCA.ShareReview.headers(),
            body: JSON.stringify({state: state})
        })
            .then(response => response.json())
            .then(data => {
                OCA.ShareReview.Backend.getData();
            });
    },

    export: function(folder, type) {
        let requestUrl = OC.generateUrl('apps/sharereview/report/export');
        fetch(requestUrl, {
            method: 'POST',
            headers: OCA.ShareReview.headers(),
            body: JSON.stringify({path: folder, type: type})
        })
            .then(response => response.json())
            .then(() => {
                OCA.ShareReview.Notification.notification('success', t('sharereview', 'Report created'));
            })
            .catch(() => {
                OCA.ShareReview.Notification.notification('error', t('sharereview', 'Request could not be processed'));
            });
    },

    saveSettings: function() {
        let folder = document.getElementById('defaultFolder').value;
        let schedule = document.getElementById('scheduleSelect').value;
        let type = document.getElementById('typeSelect').value;
        let folderOwner = document.getElementById('folderOwner').value;

        let requestUrl = OC.generateUrl('apps/sharereview/report/settings');
        fetch(requestUrl, {
            method: 'POST',
            headers: OCA.ShareReview.headers(),
            body: JSON.stringify({folder: folder, schedule: schedule, folderOwner: folderOwner, type: type})
        })
            .then(response => response.json())
            .then(() => {
                OCA.ShareReview.Notification.notification('success', t('sharereview', 'Settings saved'));
            });
    },
};

document.addEventListener('DOMContentLoaded', function () {
    OCA.ShareReview.Navigation.buildNavigation();
    OCA.ShareReview.Navigation.handleNewSharesNavigation();
    OCA.ShareReview.UI.initBulkActions();
    let defaultFolderInput = document.getElementById('defaultFolder');
    if (defaultFolderInput) {
        let openPicker = function () {
            OC.dialogs.filepicker(t('sharereview', 'Select folder'), function (path) {
                if (path) {
                    defaultFolderInput.value = path;
                    document.getElementById('folderOwner').value = OC.currentUser;
                }
            }, false, 'httpd/unix-directory', true, 1);
        };
        defaultFolderInput.addEventListener('click', openPicker);
    }
    let exportCsv = document.getElementById('exportCsv');
    if (exportCsv) {
        exportCsv.addEventListener('click', function () {
            OCA.ShareReview.Navigation.handleExportClick('csv');
        });
    }
    let exportPdf = document.getElementById('exportPdf');
    if (exportPdf) {
        exportPdf.addEventListener('click', function () {
            OCA.ShareReview.Navigation.handleExportClick('pdf');
        });
    }
    let save = document.getElementById('saveSettings');
    if (save) {
        save.addEventListener('click', OCA.ShareReview.Backend.saveSettings);
    }
});
