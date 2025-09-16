import $ from 'jquery';
import FormEngine from "@typo3/backend/form-engine.js";
import {default as Modal} from "@typo3/backend/modal.js";
import Severity from "@typo3/backend/severity.js";
import {selector} from "@typo3/core/literals.js";

class FormEngineRefresh {
    constructor() {
        this.registerClickHandler();
    }

    registerClickHandler() {
        const instance = this;
        document.querySelectorAll('[data-cantofal-id]').forEach(function (element) {
            element.addEventListener('click', function () {
                const id = element.dataset.cantofalId

                const $actionElement = $('<input />').attr('type', 'hidden').attr('name', 'cantoFileId').attr('value', id);
                const $form = $(selector`form[name="${FormEngine.formName}"]`);

                if (FormEngine.hasChange()) {
                    instance.showReloadModal($form, $actionElement);
                } else {
                    $form.append($actionElement);
                    FormEngine.formElement.submit();
                }
            })
        });
    }

    showReloadModal($form, $actionElement) {
        const title = TYPO3.lang["file_reload.label.confirm.title"] || "Do you want to save before reloading?",
            content = TYPO3.lang["file_reload.label.confirm.content"] || "You need to save your changes before reloading asset information. Do you want to save and reload now?";
        const cancel = {
            text: TYPO3.lang["file_reload.buttons.confirm.cancel"] || "Cancel",
            btnClass: "btn-default",
            name: "cancel"
        }, no = {
            text: TYPO3.lang["file_reload.buttons.confirm.no"] || "No, just reload",
            btnClass: "btn-default",
            name: "no"
        }, yes = {
            text: TYPO3.lang["file_reload.buttons.confirm.yes"] || "Yes, save and reload now",
            btnClass: "btn-primary",
            name: "yes",
            active: true
        };
        Modal.confirm(title, content, Severity.info, [cancel, no, yes]).addEventListener("button.clicked", (function (event) {
            Modal.dismiss();
            switch (event.target.name) {
                case 'no':
                    $form.append($actionElement);
                    FormEngine.formElement.submit();
                    break;
                case 'yes':
                    $form.append($actionElement);
                    FormEngine.saveDocument();
                    break;
            }
        }))
    }
}

export default new FormEngineRefresh;
