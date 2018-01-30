import * as $ from 'jquery';

'use strict';

import {ActionRequest, ActionResponse} from "./Actions";
import {BaseAction} from "./AbstractAction";

export class SaveAction extends BaseAction {

    public execute(actionPayload: ActionRequest, requestURI: string): Promise<ActionResponse> {
        return new Promise((resolve) => {
            let $form = $(document.createElement('form'))
                .css({
                    display: 'none'
                })
                .attr('method', 'POST')
                .attr('action', this.url)
                .attr('target', '_blank');

            for (let i = 0; i < actionPayload.itemIds.length; i++) {
                $form.append($('<input>').attr('name','itemIds[]').val(actionPayload.itemIds[i]));
            }

            $form.append($('<input>').attr('name', 'action').val(actionPayload.action));

            $('body').append($form);
            $form.trigger('submit');

            resolve();
        });
    }
}