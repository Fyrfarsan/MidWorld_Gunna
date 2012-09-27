<?php
function template_init()
{
	global $context, $settings, $options, $txt, $twig, $sourcedir;

	$settings['use_default_images'] = 'never';
	$settings['doctype'] = 'xhtml';
	$settings['theme_version'] = '2.0';
	$settings['use_tabs'] = true;
	$settings['use_buttons'] = true;
	$settings['separate_sticky_lock'] = true;
	$settings['strict_doctype'] = false;
	$settings['message_index_preview'] = false;
	$settings['require_theme_strings'] = false;

	require_once $sourcedir . '/libs/Twig/Autoloader.php';
	Twig_Autoloader::register();

	$loader = new Twig_Loader_Filesystem($settings['theme_dir'] . '/templates');
	$twig = new Twig_Environment($loader, array(
	    'cache' => $settings['theme_dir'] . '/cache',
	    'auto_reload' => true,
	));
}

function template_html_above()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $twig;

	$vm = array(
		'settings' => array(
			'defaultThemeUrl' 	=> $settings['default_theme_url'],
			'imagesUrl' 		=> $settings['images_url'],
			'scriptUrl' 		=> $scripturl,
			'themeUrl' 			=> $settings['theme_url'],
			'themeVariant' 		=> $settings['theme_variant'],
			),
		'html' => array(
			'showRtlDir' 		=> (($context['right_to_left']) ? true : false),
			),
		'head' => array(
			'canonicalUrl' 		=> !empty($context['canonical_url']),
			'characterSet' 		=> $context['character_set'], 
			'cssFix' 			=> null,
			'currentBoard' 		=> $context['current_board'],
			'currentTopic' 		=> $context['current_topic'],
			'forumName'			=> $context['forum_name_html_safe'],
			'headers' 			=> $context['html_headers'],
			'isoCaseFolding' 	=> $context['server']['iso_case_folding'] ? 'true' : 'false',
			'metaKeywords' 		=> $context['meta_keywords'],
			'pageTitle' 		=> $context['page_title_html_safe'],
			'robotNoIndex' 		=> !empty($context['robotNoIndex']),
			'showPMPopup' 		=> (($context['show_pm_popup']) ? true : false),
			'showRss' 			=> (!empty($modSettings['xmlnews_enable']) && (!empty($modSettings['allow_guestAccess']) || $context['user']['is_logged'])),
			),
		'txt' => array(
			'rss' 					=> $txt['rss'],
			'showPrsonalMessages' 	=> $txt['show_personal_messages'],
			'ajaxInProgress' 		=> $txt['ajax_in_progress'],
			'modifyCancel' 			=> $txt['modify_cancel'],
			),
		);

	foreach (array('ie7', 'ie6', 'webkit') as $cssfix)
		if ($context['browser']['is_' . $cssfix])
			$vm['head']['cssFix'] = $cssfix;

	echo $twig->render('index.html.above.template.html', $vm);
}

function template_body_above()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $twig, $user_info, $smcFunc;

  	$primaryGroupName = '';

  	if (!empty($user_info['groups'])) {
		$result = $smcFunc['db_query']('', '
			SELECT group_name FROM smf_membergroups where id_group = {int:id_group}',
			array(
				'id_group' => $user_info['groups'][0],
			)
		);

		list ($primaryGroupName) = $smcFunc['db_fetch_row']($result);
		$smcFunc['db_free_result']($result);
  	}

	$vm = array(
		'settings' => array(
			'defaultThemeUrl' => $settings['default_theme_url'],
			'imagesUrl' => $settings['images_url'],
			'scriptUrl' => $scripturl,
			'themeUrl' => $settings['theme_url'],
			'themeVariant' => $settings['theme_variant'],
			'isInMaintenanceMode' => ($context['in_maintenance']) ? true : false,
			'isAdmin' => ($context['user']['is_admin']) ? true : false,
			'isNewsEnabled' => (!empty($settings['enable_news'])),
			),
		'content' => array(
			'showForumWidth' => !empty($settings['forum_width']),
			'forumWidth' => $settings['forum_width'],
			'showHeaderLogo' => !empty($context['header_logo_url_html_safe']),
			'forumName' => $context['forum_name'],
			'headerLogo' => $context['header_logo_url_html_safe'],
			'showSiteSlogan' => !empty($settings['site_slogan']),
			'siteSlogan' => $settings['site_slogan'],
			'isHeaderCollapsed' => !empty($options['collapse_header']),
			'isUserLoggedIn' => ($context['user']['is_logged']) ? true : false,
			'showAvatar' => !empty($context['user']['avatar']),
			'avatarImageHtml' => $context['user']['avatar']['image'],
			'username' => $context['user']['name'],
			'unapprovedMemberCount' => (!empty($context['unapproved_members'])) ? (int) $context['unapproved_members'] : 0,
			'showModReports' => ($context['show_open_reports']) ? true : false,
			'modReportCount' => (!empty($context['open_mod_reports'])) ? (int) $context['open_mod_reports'] : 0,
			'currentTime' => $context['current_time'],
			'showLoginBar' => (!empty($context['show_login_bar'])),
			'characterSet' => $context['character_set'], 
			'disableLoginHashing' => (!(empty($context['disable_login_hashing']))),
			'sessionID' => $context['session_id'],
			'sessionVar' => $context['session_var'],
			'showOpenID' => (!empty($modSettings['enableOpenID'])),
			'moderationReportsWaiting' => sprintf($txt['mod_reports_waiting'], $context['open_mod_reports']),
			'welcomeGuest' => sprintf($txt['welcome_guest'], $txt['guest_title']),
			'currentTopidID' => (!empty($context['current_topic'])) ? (int) $context['current_topic'] : 0,
			'currentBoardID' => (!empty($context['current_board'])) ? (int) $context['current_board'] : 0,
			'newslineHtml' => $context['random_news_line'],
			'isGuest' => ($context['user']['is_guest']) ? true : false,
			'templateMenu' => '',
			'templateLinktree' => '',
			'primaryGroupName' 	=> $primaryGroupName,
			),
		'txt' => array(
			'upshrinkDescription' => $txt['upshrink_description'],
			'hello' => $txt['hello_member_ndt'],
			'unreadTopics' => $txt['unread_since_visit'],
			'unreadReplies' => $txt['show_unread_replies'],
			'maintenanceModeOn' => $txt['maintain_mode_on'],
			'approvalMessagePrefix' => '',
			'approvalMessageCount' => '',
			'approvalMessageSuffix' => $txt['approve_members_waiting'],
			'oneHour' => $txt['one_hour'],
			'oneDay' => $txt['one_day'],
			'oneWeek' => $txt['one_week'],
			'oneMonth' => $txt['one_month'],
			'forever' => $txt['forever'],
			'login' => $txt['login'],
			'quickLoginDescription' => $txt['quick_login_dec'],
			'search' => $txt['search'],
			'news' => $txt['news'],
			),
		);

	$vm['txt']['approvalMessagePrefix'] = ($vm['content']['unapprovedMemberCount'] == 1) ? $txt['approve_thereis'] : $txt['approve_thereare'];
	$vm['txt']['approvalMessageCount'] = ($vm['content']['unapprovedMemberCount'] == 1) ? $txt['approve_member'] : ($context['unapproved_members'] . ' ' . $txt['approve_members']);

	ob_start();
	template_menu();
	$vm['content']['templateMenu'] = ob_get_contents();
	ob_end_clean();

	ob_start();
	theme_linktree();
	$vm['content']['templateLinktree'] = ob_get_contents();
	ob_end_clean();

	echo $twig->render('index.body.above.template.html', $vm);
}

function template_body_below()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $twig;

	$vm = array(
		'settings' => array(
			'defaultThemeUrl' => $settings['default_theme_url'],
			'imagesUrl' => $settings['images_url'],
			'scriptUrl' => $scripturl,
			'themeUrl' => $settings['theme_url'],
			'themeVariant' => $settings['theme_variant'],
			),
		'footer' => array(
			'copyright' => '',
			'showRss' => (!empty($modSettings['xmlnews_enable']) && (!empty($modSettings['allow_guestAccess']) || $context['user']['is_logged'])),
			'showLoadTime' => ($context['show_load_time']) ? true : false,
			'loadTime' => $context['load_time'],
			'loadQueries' => $context['load_queries'],
			'showForumWidth' => !empty($settings['forum_width']),
			),
		'txt' => array(
			'validXhtml' => $txt['valid_xhtml'],
			'xhtml' => $txt['xhtml'],
			'rss' => $txt['rss'],
			'wap2' => $txt['wap2'],
			'pageCreated' => $txt['page_created'],
			'secondsWith' => $txt['seconds_with'],
			'queries' => $txt['queries'],
			),
		);

	// The 'theme_copyright()' method will attempt to 'echo' out content.  It has to be captured,
	// and added to a variable for the template to work.
	ob_start();
	theme_copyright();
	$vm['footer']['copyright'] = ob_get_contents();
	ob_end_clean();

	echo $twig->render('index.body.below.template.html', $vm);
}

function template_html_below()
{
	global $twig;

	echo $twig->render('index.html.below.template.html');
}

function theme_linktree($force_show = false)
{
	global $context, $settings, $options, $shown_linktree, $twig;

	if (empty($context['linktree']) || (!empty($context['dont_default_linktree']) && !$force_show))
		return;

	$vm = array(
		'settings' => array(
			'defaultThemeUrl' => $settings['default_theme_url'],
			'imagesUrl' => $settings['images_url'],
			'scriptUrl' => $scripturl,
			'themeUrl' => $settings['theme_url'],
			'linktreeLink' => $settings['linktree_link'],
			),
		'linktree' => $context['linktree'],
		);

	echo $twig->render('index.linktree.template.html', $vm);

	$shown_linktree = true;
}

function template_menu()
{
	global $context, $settings, $options, $scripturl, $txt, $twig;

	$vm = array(
		'settings' => array(
			'defaultThemeUrl' => $settings['default_theme_url'],
			'imagesUrl' => $settings['images_url'],
			'scriptUrl' => $scripturl,
			'themeUrl' => $settings['theme_url'],
			'linktreeLink' => $settings['linktree_link'],
			),
		'menu' => $context['menu_buttons'],
		);

	echo $twig->render('index.menu.template.html', $vm);
}

function template_button_strip($button_strip, $direction = 'top', $strip_options = array())
{
	global $settings, $context, $txt, $scripturl, $twig;

	$count = 0;

	if (!is_array($strip_options))
		$strip_options = array();

	$buttons = array();

	if ($context['right_to_left'])
		$button_strip = array_reverse($button_strip, true);

	foreach ($button_strip as $key => $value)
	{
		if (!isset($value['test']) || !empty($context[$value['test']])) {
			$value['label'] = $txt[$value['text']];
			$buttons[] = $value;
		}
	}

	if (empty($buttons))
		return;

	$vm = array(
		'settings' => array(
			'defaultThemeUrl' => $settings['default_theme_url'],
			'imagesUrl' => $settings['images_url'],
			'scriptUrl' => $scripturl,
			'themeUrl' => $settings['theme_url'],
			'linktreeLink' => $settings['linktree_link'],
			),
		'buttons' => $buttons,
		'direction' => $direction,
		'stripOptions' => $strip_options,
		);

	echo $twig->render('index.buttonstrip.template.html', $vm);
} ?>