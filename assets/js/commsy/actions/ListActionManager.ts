import * as $ from 'jquery';
import * as Actions from './Actions';
import {ActionExecuter, ListActionData} from "./Actions";
import {BaseAction} from "./AbstractAction";

/*
 Action in template:

 <a href="#" class="commsy-select-action" data-uk-button data-commsy-list-action='{"target":".feed", "actionUrl": "{{ path('commsy_user_feedaction', {'roomId': roomId}) }}", "action": "user-delete"}'>
 <i class="uk-icon-justify uk-icon-small uk-icon-remove uk-visible-large"></i> {{ 'delete'|trans({},'user')|capitalize }}
 </a>

 - "class" must be "commsy-select-action"
 - "data-commsy-list-action" must contain the following values:
 - "target"      -> usualy the div where feed-entries can be selected and the returned feed-entries from the ajax call are inserted
 - "actionUrl"   -> path to controller
 - "action"      -> key that is send to controller
 */

'use strict';

export class ListActionManager {
    private currentActionData: ListActionData;
    private actionActor: JQuery;

    private selectMode: boolean = false;

    private selectAll: boolean = false;
    private numCurrentSelected: number = 0;

    public bootstrap() {
        this.registerClickEvents();

        window.addEventListener('feedDidLoad', () => {
            this.onFeedLoad();
        });
    }

    private onFeedLoad() {
        if (this.selectMode) {
            let selectAll: boolean = this.selectAll;

            this.onStopEdit();
            this.onStartEdit();

            if (selectAll) {
                this.onSelectAll();
            }
        }
    }

    private registerClickEvents() {
        // register all actions listed in the dropdown menu, identified by .commsy-select-action
        $('.commsy-select-action').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            // store data from data-comsy-list-action
            this.currentActionData = $(event.currentTarget).data('cs-action');

            // store actor to get needed data later on
            this.actionActor = $(event.currentTarget);

            if (this.currentActionData.mode == 'selection') {
                this.onStartEdit();
            }
        });

        // confirm action button
        $('#commsy-select-actions-ok').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onClickPerform();
        });

        // cancel action button
        $('#commsy-select-actions-cancel').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onStopEdit();
        });

        // select all
        $('#commsy-select-actions-select-all').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onSelectAll();
        });

        // deselect all
        $('#commsy-select-actions-unselect').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onDeselectAll($(event.currentTarget));
        });
    }

    private onSelectAll() {
        // highlight button as active
        this.actionActor.addClass('uk-active');

        let $feed: JQuery = $('.feed ul');

        // check all visible checkboxes
        $feed.find('input').filter(":visible").each(function() {
            let element = <HTMLInputElement>this;
            if (element.type == 'checkbox') {
                $(element).prop('checked', true);
            }
        });

        // highlight checked articles
        $feed.find('article').filter(":visible").each(function() {
            $(this).addClass('uk-comment-primary');
        });

        // update selected entries counter
        let $listCountAll: JQuery = $('#commsy-list-count-all');
        this.numCurrentSelected = parseInt($listCountAll.html());
        this.updateCurrentSelected();

        // persist select all
        this.selectAll = true;
    }

    private onDeselectAll($actor: JQuery) {
        // undo select all modifications
        $actor.removeClass('uk-active');

        let $feed: JQuery = $('.feed ul');

        $feed.find('input').each(function() {
            let element = <HTMLInputElement>this;
            if (element.type == 'checkbox') {
                $(element).prop('checked', false);
            }
        });

        $feed.find('article').each(function() {
            $(this).removeClass('uk-comment-primary');
        });

        this.numCurrentSelected = 0;
        this.updateCurrentSelected();

        this.selectAll = false;
    }

    private onStartEdit() {
        this.onStopEdit();
        this.selectMode = true;

        let $feed: JQuery = $('.feed ul');
        if (!$feed.length) {
            return;
        }

        this.addCheckboxes($feed.find('article'));

        // show the action dialog
        let $actionDialog: JQuery = $('#commsy-select-actions');
        $actionDialog
            .removeClass('uk-hidden')
            .parent('.uk-sticky-placeholder')
                .css('height', '65px');

        // reset current selected count
        this.numCurrentSelected = 0;
        this.updateCurrentSelected();

        // hide normal list count / show edit count
        $('#commsy-list-count-display').addClass('uk-hidden');
        $('#commsy-list-count-edit').removeClass('uk-hidden');

        // reset dialog state
        $('#commsy-select-actions-select-all').removeClass('uk-active');
        $('#commsy-select-actions-unselect').removeClass('uk-active');
        $('#commsy-select-actions-ok').removeClass('uk-active');
        $('#commsy-select-actions-cancel').removeClass('uk-active');

        $(".feed .uk-grid.uk-text-truncate div").css("padding-left", "0px");
        $(".feed .uk-grid .uk-icon-sign-in").toggleClass("uk-hidden");

        // reset select all
        this.selectAll = false;

        this.registerArticleEvents();
    }

    private onStopEdit() {
        this.selectMode = false;

        let $feed: JQuery = $('.feed ul');
        if (!$feed.length) {
            return;
        }

        // hide the action dialog
        let $actionDialog: JQuery = $('#commsy-select-actions');
        $actionDialog
            .addClass('uk-hidden')
            .parent('.uk-sticky-placeholder')
                .css('height', '0px');

        // uncheck all checkboxes
        $feed.find('input').each(function() {
            let element = <HTMLInputElement>this;
            if (element.type == 'checkbox') {
                $(element).prop('checked', false);
            }
        });

        // reset articles
        $feed.find('article').
            each(function() {
                $(this).removeClass('uk-comment-primary');
            })
            .removeClass('selectable');


        // TODO: what is this for?
        // $(this).html($(this).data('title'));

        this.numCurrentSelected = 0;
        this.updateCurrentSelected();

        // show normal list count / hide edit count
        $('#commsy-list-count-display').removeClass('uk-hidden');
        $('#commsy-list-count-edit').addClass('uk-hidden');

        $(".feed .uk-grid.uk-text-truncate div").css("padding-left", "35px");
        $(".feed .uk-grid .uk-icon-sign-in").toggleClass("uk-hidden");

        this.selectAll = false;
    }

    private onClickPerform() {
        // collect values of selected checkboxes
        let $feed: JQuery = $('.feed ul');

        let itemIds: number[] = [];
        $feed.find('input:checked').each(function(index, element) {
            itemIds.push(Number($(element).val()));
        });

        // if no entries are selected, present notification
        if (itemIds.length == 0) {
            UIkit.notify({
                message : this.currentActionData.noSelectionMessage,
                status  : 'warning',
                timeout : 5550,
                pos     : 'top-center'
            });

            return;
        }

        let action: BaseAction = Actions.createAction(this.currentActionData);
        let actionExecuter: ActionExecuter = new ActionExecuter();
        actionExecuter.invokeListAction(this.actionActor, action, itemIds, this.selectAll, 0)
            .then(() => {
                $('#commsy-select-actions-select-all').removeClass('uk-active');
                $('#commsy-select-actions-unselect').removeClass('uk-active');

                $feed.find('input[type="checkbox"]').each(function () {
                    $(this).prop('checked', false);
                });
                $feed.find('article').each(function () {
                    $(this).removeClass('uk-comment-primary');
                });

                this.onStopEdit();
            })
            .catch( (error: Error) => {
                // Catching here does not have to be a fatal error, e.g. rejecting a confirm dialog.
                // So we check for the error parameter
                if (error) {
                    UIkit.notify(error.message, 'danger');
                }
            });
    }

    private addCheckboxes($articles: JQuery) {
        let currentAction: string = this.currentActionData.action;

        $articles.each(function() {
            // each article has a data attribute listing the allowed actions
            if ($.inArray(currentAction, $(this).data('allowed-actions')) > -1) {
                $(this).toggleClass('selectable', true);
            }
        });
    }

    private updateCurrentSelected() {
        $('#commsy-list-count-selected').html(this.numCurrentSelected.toString());
    }

    private registerArticleEvents() {
        let $feed: JQuery = $('.feed ul');

        // handle click on article
        $feed.find('article').off().on('click', (event) => {
            let $article: JQuery = $(event.currentTarget);

            // select mode?
            if ($article.hasClass('selectable')) {
                let checkbox: JQuery = $article.find('input[type="checkbox"]').first();

                // only select if element has a checkbox
                if (checkbox.length) {
                    // highlight the article
                    $article.toggleClass('uk-comment-primary');

                    // toggle checkbox
                    checkbox.prop('checked', $article.hasClass('uk-comment-primary'));

                    if (checkbox.prop('checked')) {
                        this.numCurrentSelected++;
                    } else {
                        this.numCurrentSelected--;
                    }
                    this.updateCurrentSelected();

                    // disable normal click behaviour
                    event.preventDefault();
                }
            }
        });

        // handle click on checkboxes
        $feed.find('input').off().on('click', function(event) {
            event.stopPropagation();
            $(this).parents('article').trigger('click');
        });
    }
}