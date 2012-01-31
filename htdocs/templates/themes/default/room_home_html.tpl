{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="Ansicht in Reihen"><img src="{$basic.tpl_path}img/btn_row_view.gif" alt="Reihen" /></a>
    <a href="" title="Ansicht in Portlets"><img src="{$basic.tpl_path}img/btn_portlet_view.gif" alt="Portlets" /></a>
{/block}

{block name=room_navigation_rubric_title}
	&Uuml;bersicht Projektraum (Home)
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		{foreach $room.home_content as $rubric}
			<div class="content_item"> <!-- Start content_item -->
            	<div class="ci_head_actions">
	            	<a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=edit" title="___HOME_SMARTY_{$rubric@key|upper}_ACTION_NEW___">
	            		<img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="___HOME_SMARTY_ACTION_NEW___" />
	            	</a>
                    <a href="" class="open_close" title="___HOME_SMARTY_ACTION_CLOSE___">
                    	<img src="{$basic.tpl_path}img/btn_ci_close.gif" alt="___HOME_SMARTY_ACTION_CLOSE___" />
                    </a>
                </div>
                <h2>
                	___COMMON_{$rubric@key|upper}_INDEX___
                	<span>
                		({$rubric.message_tag})
                	</span>
                </h2>

                <div class="clear"> </div>

                <div class="list_wrap">

	                {foreach $rubric.items as $item}
	                	<div class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_home{else}even_sep_home{/if}">
	                    	<div class="column_380">
	                        	{if $rubric@key == 'discussion'}
	                        	<p class="column_addon">
	                        		{$item.column_1_addon}
	                        	</p>
	                        	{/if}
	                        	<p>
								{if $item.noticed != ''}
									<a href="" class="new_item"><img title="{$item.noticed}" class="new_item" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
         						{/if}
	                            	<a href="commsy.php?cid={$environment.cid}&mod={$rubric@key}&fct=detail&iid={$item.iid}">{$item.column_1}</a>
	                            </p>
	                        </div>
                        	<div class="column_140">
                            	<p>{$item.column_2}</p>
                            </div>
	                        <div class="column_194">
	                        	<p>
	                        	{if $rubric@key == 'material' or $rubric@key == 'announcement' or $rubric@key == 'discussion'}
	                            	<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.user_iid}">{$item.column_3}</a>
	                        	{else}
	                        		{$item.column_3}
	                            {/if}
	                            </p>
	                        </div>
		                    <div class="clear"> </div>
	                    </div>
	                {/foreach}

                </div>
            </div> <!-- Ende content_item -->
		{/foreach}
	</div>
{/block}