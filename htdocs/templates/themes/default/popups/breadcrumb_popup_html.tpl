<div id="popup_wrapper">
	<div id="popup_background"></div>

	<div class="tm_dropmenu hidden">
		<div class="tm_di_ground_solid">

			<div class="popup">
				<div id="popup_content">
					<div class="tab_navigation">
						<p>
							Sie sind hier:
								{foreach $popup.breadcrumb as $crumb}
									{if !$crumb@first}
										<span class="tm_bcb_next">
									{/if}
									{if !$crumb@last}
										<a class="tab_navigation" href="commsy.php?cid={$crumb.id}&mod=home&fct=index">{$crumb.title|truncate:40:'...':true}</a>
									{else}
										<strong>{$crumb.title|truncate:40:'...':true}</strong>
									{/if}

									{if !$crumb@first}
										</span>
									{/if}
								{/foreach}
							</p>
					</div>
					<div id="profile_content_row_three">
						<table>
							<tr>
						{assign var="i" value="0"}
						{foreach $popup.rooms as $room}
						   {if $room.title == '------------------------------------'}
						   {else}
							<td>
							<a class="room_change_item" title="{$room.title}" href="commsy.php?cid={$room.item_id}&mod=home&fct=index">
							<div class="room_change_content">
								<div class="room_change_room_box">
								<div class="room_change_title">
							    	<h3 class="room_change_title_h3"> {$room.title|truncate:28:'...':true} </h3>
							    </div>
							    <div class="room_change_content_element_wrapper">
								{if !$room.color_array.content_background}
							    <div class="room_change_content_element">
								{else}
							    <div class="room_change_content_element" style="background-color:{$room.color_array.tabs_background}; text-shadow: 0 0px #999; background-image:none; color:{$room.color_array.tabs_title}">
								{/if}
									<p>
										{if $room.new_entries == 1}
											{i18n tag=ACTIVITY_NEW_ENTRIES_NEW_SINGULAR param1=$room.time_spread}: {$room.new_entries}
										{else}
											{i18n tag=ACTIVITY_NEW_ENTRIES_NEW param1=$room.time_spread}: {$room.new_entries}
										{/if}

									</p>
									<p>___ACTIVITY_PAGE_IMPRESSIONS___: {$room.page_impressions}</p>
									<p class="float-left">___ACTIVITY_ACTIVE_MEMBERS_DESC_NEW___: {$room.activity_array.active} / {$room.activity_array.all_users}</p>
								</div>
								</div>
								</div>
								<div class="clear"> </div>
							</div>
							</a>
							<td>
							{if $i == 3 && !$room@last}
								</tr>
								<tr>
								{$i = 0}
							{else}
								{$i = $i + 1}
							{/if}
							{/if}
						{/foreach}
						</tr>
						</table>

						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>