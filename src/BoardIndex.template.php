<?php
/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines
 * @copyright 2011 Simple Machines
 * @license http://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.0
 */

function template_main()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings, $twig;

	$vm = array(
		'context' => array(
			'sessionID' => $context['session_id'],
			'sessionVar' => $context['session_var'],
			'isGuest' => ($context['user']['is_guest']) ? true : false,
			),
		'options' => array(
			'collapseNewsFader' => (!empty($options['collapse_news_fader']) ? true : false),
			),
		'settings' => array(
			'defaultThemeUrl' => $settings['default_theme_url'],
			'imagesUrl' => $settings['images_url'],
			'scriptUrl' => $scripturl,
			'themeUrl' => $settings['theme_url'],
			'variantThemeUrl' => $settings['theme_variant_url'],
			'showStatsIndex' => ($settings['show_stats_index']) ? true : false,
			'showLatestMember' => ($settings['show_latest_member']) ? true : false,
			'showNewsFader' => ($settings['show_newsfader'] && !empty($context['fader_news_lines'])) ? true : false,
			'newsfaderTimeout' => (!empty($settings['newsfader_time']) ? (int) $settings['newsfader_time'] : 5000),
			),
		'headlines' => $context['news_lines'],
		'stats' => array(
			'totalMembers' => $context['common_stats']['total_members'],
			'totalTopics' => $context['common_stats']['total_topics'],
			'linkToLatestMember' => $context['common_stats']['latest_member']['link'],
			),
		'categories' => array(),
		'txt' => array(
			'members' => $txt['members'],
			'postsMade' => $txt['posts_made'],
			'topics' => $txt['topics'],
			'welcomeMember' => $txt['welcome_member'],
			'newestMember' => $txt['newest_member'],
			'upshrinkDescription' => $txt['upshrink_description'],
			'news' => $txt['news'],
			'viewUnreadCategory' => $txt['view_unread_category'],
			'newPosts' => $txt['new_posts'],
			'oldPosts' => $txt['old_posts'],
			'redirects' => $txt['redirects'],
			'posts' => $txt['posts'],
			'boardTopics' => $txt['board_topics'],
			'lastPost' => $txt['last_post'],
			'by' => $txt['by'],
			'in' => $txt['in'],
			'on' => $txt['on'],
			'parentBoards' => $txt['parent_boards'],
			),
		);

	foreach ($context['categories'] as $category)
	{
		// If theres no parent boards we can see, avoid showing an empty category (unless its collapsed)
		if (empty($category['boards']) && !$category['is_collapsed'])
			continue;

		$vmCategory = array(
			'ID' => $category['id'],
			'boards' => array(),
			'canCollapse' => ($category['can_collapse']) ? true : false,
			'collapseHref' => $category['collapse_href'],
			'collapseImage' => $category['collapse_image'],
			'showUnreadLink' => (!$context['user']['is_guest'] && !empty($category['show_unread'])),
			'link' => $category['link'],
			'isCollapsed' => (!$category['is_collapsed']) ? true : false,
			);

		// Assuming the category hasn't been collapsed...
		if (!$category['is_collapsed'])
		{
			foreach ($category['boards'] as $board)
			{
				$vmBoard = array(
					'ID' => $board['id'],
					'name' => $board['name'],
					'description' => $board['description'],
					'hasChildren' => !empty($board['children']),
					'hasModerators' => !empty($board['moderators']),
					'href' => $board['href'],
					'unreadHref' =>  (($board['is_redirect'] || $context['user']['is_guest']) ? $board['href'] : $scripturl . '?action=unread;board=' . $board['id'] . '.0;children'),
					'isNew' => ($board['new']) ? true : false,
					'isRedirect' => ($board['is_redirect']) ? true : false,
					'newChildren' => ($board['children_new']) ? true : false,
					'showUnapprovedPostLink' => ($board['can_approve_posts'] && ($board['unapproved_posts'] || $board['unapproved_topics'])),
					'unapprovedPostHref' => $scripturl . '?action=moderate;area=postmod;sa=' . ($board['unapproved_topics'] > 0 ? 'topics' : 'posts') . ';brd=' . $board['id'] . ';' . $context['session_var'] . '=' . $context['session_id'],
					'unapprovedPostTitle' => sprintf($txt['unapproved_posts'], $board['unapproved_topics'], $board['unapproved_posts']),
					'moderatorLinkPrefix' => ((count($board['moderators']) == 1) ? $txt['moderator'] : $txt['moderators']),
					'moderators' => $board['link_moderators'],
					'postCountText' => comma_format($board['posts']),
					'topicCountText' => comma_format($board['topics']),
					'doesLastPostHaveID' => !empty($board['last_post']['id']),
					'lastPostLink' => $board['last_post']['link'],
					'lastPostTime' => $board['last_post']['time'],
					'lastPostMemberLink' => $board['last_post']['member']['link'],
					'children' => array(),
					);

				if (!empty($board['children']))
				{
					// Sort the links into an array with new boards bold so it can be imploded.
					/* Each child in each board's children has:
							id, name, description, new (is it new?), topics (#), posts (#), href, link, and last_post. */
					foreach ($board['children'] as $child)
					{
						if (!$child['is_redirect'])
							$child['link'] = '<a href="' . $child['href'] . '" ' . ($child['new'] ? 'class="new_posts" ' : '') . 'title="' . ($child['new'] ? $txt['new_posts'] : $txt['old_posts']) . ' (' . $txt['board_topics'] . ': ' . comma_format($child['topics']) . ', ' . $txt['posts'] . ': ' . comma_format($child['posts']) . ')">' . $child['name'] . ($child['new'] ? '</a> <a href="' . $scripturl . '?action=unread;board=' . $child['id'] . '" title="' . $txt['new_posts'] . ' (' . $txt['board_topics'] . ': ' . comma_format($child['topics']) . ', ' . $txt['posts'] . ': ' . comma_format($child['posts']) . ')"><img src="' . $settings['lang_images_url'] . '/new.gif" class="new_posts" alt="" />' : '') . '</a>';
						else
							$child['link'] = '<a href="' . $child['href'] . '" title="' . comma_format($child['posts']) . ' ' . $txt['redirects'] . '">' . $child['name'] . '</a>';

						// Has it posts awaiting approval?
						if ($child['can_approve_posts'] && ($child['unapproved_posts'] || $child['unapproved_topics']))
							$child['link'] .= ' <a href="' . $scripturl . '?action=moderate;area=postmod;sa=' . ($child['unapproved_topics'] > 0 ? 'topics' : 'posts') . ';brd=' . $child['id'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '" title="' . sprintf($txt['unapproved_posts'], $child['unapproved_topics'], $child['unapproved_posts']) . '" class="moderation_link">(!)</a>';

						$children[] = $child['new'] ? '<strong>' . $child['link'] . '</strong>' : $child['link'];
					}

					$vmBoard['children'] = $children;
				}

				$vmCategory['boards'][] = $vmBoard;
			}
		}

		$vm['categories'][] = $vmCategory;
	}

	echo '<pre>';
	//print_r($context);
	echo '</pre>';

	echo $twig->render('BoardIndex.main.template.html', $vm);
/*
	if ($context['user']['is_logged'])
	{
		echo '
	<div id="posting_icons" class="floatleft">';

		// Mark read button.
		$mark_read_button = array(
			'markread' => array('text' => 'mark_as_read', 'image' => 'markread.gif', 'lang' => true, 'url' => $scripturl . '?action=markasread;sa=all;' . $context['session_var'] . '=' . $context['session_id']),
		);

		echo '
		<ul class="reset">
			<li class="floatleft"><img src="', $settings['images_url'], '/', $context['theme_variant_url'], 'new_some.png" alt="" /> ', $txt['new_posts'], '</li>
			<li class="floatleft"><img src="', $settings['images_url'], '/', $context['theme_variant_url'], 'new_none.png" alt="" /> ', $txt['old_posts'], '</li>
			<li class="floatleft"><img src="', $settings['images_url'], '/', $context['theme_variant_url'], 'new_redirect.png" alt="" /> ', $txt['redirect_board'], '</li>
		</ul>
	</div>';

		// Show the mark all as read button?
		if ($settings['show_mark_read'] && !empty($context['categories']))
			echo '<div class="mark_read">', template_button_strip($mark_read_button, 'right'), '</div>';
	}
	else
	{
		echo '
	<div id="posting_icons" class="flow_hidden">
		<ul class="reset">
			<li class="floatleft"><img src="', $settings['images_url'], '/new_none.png" alt="" /> ', $txt['old_posts'], '</li>
			<li class="floatleft"><img src="', $settings['images_url'], '/new_redirect.png" alt="" /> ', $txt['redirect_board'], '</li>
		</ul>
	</div>';
	}

	template_info_center();
	*/
}

function template_info_center()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	// Here's where the "Info Center" starts...
	echo '
	<span class="clear upperframe"><span></span></span>
	<div class="roundframe"><div class="innerframe">
		<div class="cat_bar">
			<h3 class="catbg">
				<img class="icon" id="upshrink_ic" src="', $settings['images_url'], '/collapse.gif" alt="*" title="', $txt['upshrink_description'], '" style="display: none;" />
				', sprintf($txt['info_center_title'], $context['forum_name_html_safe']), '
			</h3>
		</div>
		<div id="upshrinkHeaderIC"', empty($options['collapse_header_ic']) ? '' : ' style="display: none;"', '>';

	// This is the "Recent Posts" bar.
	if (!empty($settings['number_recent_posts']) && (!empty($context['latest_posts']) || !empty($context['latest_post'])))
	{
		echo '
			<div class="title_barIC">
				<h4 class="titlebg">
					<span class="ie6_header floatleft">
						<a href="', $scripturl, '?action=recent"><img class="icon" src="', $settings['images_url'], '/post/xx.gif" alt="', $txt['recent_posts'], '" /></a>
						', $txt['recent_posts'], '
					</span>
				</h4>
			</div>
			<div class="hslice" id="recent_posts_content">
				<div class="entry-title" style="display: none;">', $context['forum_name_html_safe'], ' - ', $txt['recent_posts'], '</div>
				<div class="entry-content" style="display: none;">
					<a rel="feedurl" href="', $scripturl, '?action=.xml;type=webslice">', $txt['subscribe_webslice'], '</a>
				</div>';

		// Only show one post.
		if ($settings['number_recent_posts'] == 1)
		{
			// latest_post has link, href, time, subject, short_subject (shortened with...), and topic. (its id.)
			echo '
				<strong><a href="', $scripturl, '?action=recent">', $txt['recent_posts'], '</a></strong>
				<p id="infocenter_onepost" class="middletext">
					', $txt['recent_view'], ' &quot;', $context['latest_post']['link'], '&quot; ', $txt['recent_updated'], ' (', $context['latest_post']['time'], ')<br />
				</p>';
		}
		// Show lots of posts.
		elseif (!empty($context['latest_posts']))
		{
			echo '
				<dl id="ic_recentposts" class="middletext">';

			/* Each post in latest_posts has:
					board (with an id, name, and link.), topic (the topic's id.), poster (with id, name, and link.),
					subject, short_subject (shortened with...), time, link, and href. */
			foreach ($context['latest_posts'] as $post)
				echo '
					<dt><strong>', $post['link'], '</strong> ', $txt['by'], ' ', $post['poster']['link'], ' (', $post['board']['link'], ')</dt>
					<dd>', $post['time'], '</dd>';
			echo '
				</dl>';
		}
		echo '
			</div>';
	}

	// Show information about events, birthdays, and holidays on the calendar.
	if ($context['show_calendar'])
	{
		echo '
			<div class="title_barIC">
				<h4 class="titlebg">
					<span class="ie6_header floatleft">
						<a href="', $scripturl, '?action=calendar' . '"><img class="icon" src="', $settings['images_url'], '/icons/calendar.gif', '" alt="', $context['calendar_only_today'] ? $txt['calendar_today'] : $txt['calendar_upcoming'], '" /></a>
						', $context['calendar_only_today'] ? $txt['calendar_today'] : $txt['calendar_upcoming'], '
					</span>
				</h4>
			</div>
			<p class="smalltext">';

		// Holidays like "Christmas", "Chanukah", and "We Love [Unknown] Day" :P.
		if (!empty($context['calendar_holidays']))
				echo '
				<span class="holiday">', $txt['calendar_prompt'], ' ', implode(', ', $context['calendar_holidays']), '</span><br />';

		// People's birthdays. Like mine. And yours, I guess. Kidding.
		if (!empty($context['calendar_birthdays']))
		{
				echo '
				<span class="birthday">', $context['calendar_only_today'] ? $txt['birthdays'] : $txt['birthdays_upcoming'], '</span> ';
		/* Each member in calendar_birthdays has:
				id, name (person), age (if they have one set?), is_last. (last in list?), and is_today (birthday is today?) */
		foreach ($context['calendar_birthdays'] as $member)
				echo '
				<a href="', $scripturl, '?action=profile;u=', $member['id'], '">', $member['is_today'] ? '<strong>' : '', $member['name'], $member['is_today'] ? '</strong>' : '', isset($member['age']) ? ' (' . $member['age'] . ')' : '', '</a>', $member['is_last'] ? '<br />' : ', ';
		}
		// Events like community get-togethers.
		if (!empty($context['calendar_events']))
		{
			echo '
				<span class="event">', $context['calendar_only_today'] ? $txt['events'] : $txt['events_upcoming'], '</span> ';
			/* Each event in calendar_events should have:
					title, href, is_last, can_edit (are they allowed?), modify_href, and is_today. */
			foreach ($context['calendar_events'] as $event)
				echo '
					', $event['can_edit'] ? '<a href="' . $event['modify_href'] . '" title="' . $txt['calendar_edit'] . '"><img src="' . $settings['images_url'] . '/icons/modify_small.gif" alt="*" /></a> ' : '', $event['href'] == '' ? '' : '<a href="' . $event['href'] . '">', $event['is_today'] ? '<strong>' . $event['title'] . '</strong>' : $event['title'], $event['href'] == '' ? '' : '</a>', $event['is_last'] ? '<br />' : ', ';
		}
		echo '
			</p>';
	}

	// Show statistical style information...
	if ($settings['show_stats_index'])
	{
		echo '
			<div class="title_barIC">
				<h4 class="titlebg">
					<span class="ie6_header floatleft">
						<a href="', $scripturl, '?action=stats"><img class="icon" src="', $settings['images_url'], '/icons/info.gif" alt="', $txt['forum_stats'], '" /></a>
						', $txt['forum_stats'], '
					</span>
				</h4>
			</div>
			<p>
				', $context['common_stats']['total_posts'], ' ', $txt['posts_made'], ' ', $txt['in'], ' ', $context['common_stats']['total_topics'], ' ', $txt['topics'], ' ', $txt['by'], ' ', $context['common_stats']['total_members'], ' ', $txt['members'], '. ', !empty($settings['show_latest_member']) ? $txt['latest_member'] . ': <strong> ' . $context['common_stats']['latest_member']['link'] . '</strong>' : '', '<br />
				', (!empty($context['latest_post']) ? $txt['latest_post'] . ': <strong>&quot;' . $context['latest_post']['link'] . '&quot;</strong>  ( ' . $context['latest_post']['time'] . ' )<br />' : ''), '
				<a href="', $scripturl, '?action=recent">', $txt['recent_view'], '</a>', $context['show_stats'] ? '<br />
				<a href="' . $scripturl . '?action=stats">' . $txt['more_stats'] . '</a>' : '', '
			</p>';
	}

	// "Users online" - in order of activity.
	echo '
			<div class="title_barIC">
				<h4 class="titlebg">
					<span class="ie6_header floatleft">
						', $context['show_who'] ? '<a href="' . $scripturl . '?action=who' . '">' : '', '<img class="icon" src="', $settings['images_url'], '/icons/online.gif', '" alt="', $txt['online_users'], '" />', $context['show_who'] ? '</a>' : '', '
						', $txt['online_users'], '
					</span>
				</h4>
			</div>
			<p class="inline stats">
				', $context['show_who'] ? '<a href="' . $scripturl . '?action=who">' : '', comma_format($context['num_guests']), ' ', $context['num_guests'] == 1 ? $txt['guest'] : $txt['guests'], ', ' . comma_format($context['num_users_online']), ' ', $context['num_users_online'] == 1 ? $txt['user'] : $txt['users'];

	// Handle hidden users and buddies.
	$bracketList = array();
	if ($context['show_buddies'])
		$bracketList[] = comma_format($context['num_buddies']) . ' ' . ($context['num_buddies'] == 1 ? $txt['buddy'] : $txt['buddies']);
	if (!empty($context['num_spiders']))
		$bracketList[] = comma_format($context['num_spiders']) . ' ' . ($context['num_spiders'] == 1 ? $txt['spider'] : $txt['spiders']);
	if (!empty($context['num_users_hidden']))
		$bracketList[] = comma_format($context['num_users_hidden']) . ' ' . $txt['hidden'];

	if (!empty($bracketList))
		echo ' (' . implode(', ', $bracketList) . ')';

	echo $context['show_who'] ? '</a>' : '', '
			</p>
			<p class="inline smalltext">';

	// Assuming there ARE users online... each user in users_online has an id, username, name, group, href, and link.
	if (!empty($context['users_online']))
	{
		echo '
				', sprintf($txt['users_active'], $modSettings['lastActive']), ':<br />', implode(', ', $context['list_users_online']);

		// Showing membergroups?
		if (!empty($settings['show_group_key']) && !empty($context['membergroups']))
			echo '
				<br />[' . implode(']&nbsp;&nbsp;[', $context['membergroups']) . ']';
	}

	echo '
			</p>
			<p class="last smalltext">
				', $txt['most_online_today'], ': <strong>', comma_format($modSettings['mostOnlineToday']), '</strong>.
				', $txt['most_online_ever'], ': ', comma_format($modSettings['mostOnline']), ' (', timeformat($modSettings['mostDate']), ')
			</p>';

	// If they are logged in, but statistical information is off... show a personal message bar.
	if ($context['user']['is_logged'] && !$settings['show_stats_index'])
	{
		echo '
			<div class="title_barIC">
				<h4 class="titlebg">
					<span class="ie6_header floatleft">
						', $context['allow_pm'] ? '<a href="' . $scripturl . '?action=pm">' : '', '<img class="icon" src="', $settings['images_url'], '/message_sm.gif" alt="', $txt['personal_message'], '" />', $context['allow_pm'] ? '</a>' : '', '
						<span>', $txt['personal_message'], '</span>
					</span>
				</h4>
			</div>
			<p class="pminfo">
				<strong><a href="', $scripturl, '?action=pm">', $txt['personal_message'], '</a></strong>
				<span class="smalltext">
					', $txt['you_have'], ' ', comma_format($context['user']['messages']), ' ', $context['user']['messages'] == 1 ? $txt['message_lowercase'] : $txt['msg_alert_messages'], '.... ', $txt['click'], ' <a href="', $scripturl, '?action=pm">', $txt['here'], '</a> ', $txt['to_view'], '
				</span>
			</p>';
	}

	echo '
		</div>
	</div></div>
	<span class="lowerframe"><span></span></span>';

	// Info center collapse object.
	echo '
	<script type="text/javascript"><!-- // --><![CDATA[
		var oInfoCenterToggle = new smc_Toggle({
			bToggleEnabled: true,
			bCurrentlyCollapsed: ', empty($options['collapse_header_ic']) ? 'false' : 'true', ',
			aSwappableContainers: [
				\'upshrinkHeaderIC\'
			],
			aSwapImages: [
				{
					sId: \'upshrink_ic\',
					srcExpanded: smf_images_url + \'/collapse.gif\',
					altExpanded: ', JavaScriptEscape($txt['upshrink_description']), ',
					srcCollapsed: smf_images_url + \'/expand.gif\',
					altCollapsed: ', JavaScriptEscape($txt['upshrink_description']), '
				}
			],
			oThemeOptions: {
				bUseThemeSettings: ', $context['user']['is_guest'] ? 'false' : 'true', ',
				sOptionName: \'collapse_header_ic\',
				sSessionVar: ', JavaScriptEscape($context['session_var']), ',
				sSessionId: ', JavaScriptEscape($context['session_id']), '
			},
			oCookieOptions: {
				bUseCookie: ', $context['user']['is_guest'] ? 'true' : 'false', ',
				sCookieName: \'upshrinkIC\'
			}
		});
	// ]]></script>';
}
?>