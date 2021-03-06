<?php
	/**
	 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
	 *
	 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
	 *
	 * @license GNU AGPL version 3 or any later version
	 *
	 *  This program is free software: you can redistribute it and/or modify
	 *  it under the terms of the GNU Affero General Public License as
	 *  published by the Free Software Foundation, either version 3 of the
	 *  License, or (at your option) any later version.
	 *
	 *  This program is distributed in the hope that it will be useful,
	 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *  GNU Affero General Public License for more details.
	 *
	 *  You should have received a copy of the GNU Affero General Public License
	 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 *
	 */

	use OCP\User; //To do: replace according to API
	use OCP\Util;
	use OCP\Template;
	use OCP\IGroupManager;


	Util::addStyle('polls', 'list');
	Util::addScript('polls', 'app');
	Util::addScript('polls', 'start');

	$userId = $_['userId'];
	/** @var \OCP\IUserManager $userMgr */
	$userMgr = $_['userMgr'];
	/** @var \OCP\IURLGenerator $urlGenerator */
	$urlGenerator = $_['urlGenerator'];
	/** @var \OCA\Polls\Db\Event[] $polls */
	$polls = $_['polls'];
	$adminMode = (\OC::$server->getGroupManager()->isAdmin($userId))
?>

	<div id="app-content">
		<div id="controls">
			<div class="breadcrumb">
				<div class="crumb svg crumbhome">
					<a class="icon-home" href="<?php p($urlGenerator->linkToRoute('polls.page.index')); ?>"> Home </a>
				</div>
			</div>
			<div class="actions creatable" style="">
				<a href="<?php p($urlGenerator->linkToRoute('polls.page.create_poll')); ?>" class="button new">
					<span class="symbol icon-add"></span><span class="hidden-visually">Neu</span>
				</a>
				<input class="stop icon-close" style="display:none" value="" type="button">
			</div>
		</div>

	<?php if (count($_['polls']) === 0) : ?>
		<div id="emptycontent" class="">
			<div class="icon-polls"></div>
			<h2><?php p($l->t('No existing polls.')); ?></h2>
			<a href="<?php p($urlGenerator->linkToRoute('polls.page.create_poll')); ?>" class="button new">
				<span><?php p($l->t('Click here to add a poll')); ?></span>
			</a>
		</div>
	<?php else : ?>

		<div class="table main-container has-controls">
			<div class ="table-row table-header">
				<div class="wrapper group-master">
					<div class="wrapper group-1">
						<div class="wrapper group-1-1">
							<div class="flex-column name">		<?php p($l->t('Title')); ?></div>
						</div>
						<div class="wrapper group-1-2">
							<div class="flex-column actions"></div>
						</div>
					</div>
					<div class="wrapper group-2">
						<div class="flex-column owner">   <?php p($l->t('By')); ?></div>
						<div class="wrapper group-2-1">
							<div class="flex-column access">	  <?php p($l->t('Access')); ?></div>
							<div class="flex-column created">		 <?php p($l->t('Created')); ?></div>
						</div>
						<div class="wrapper group-2-2">
							<div class="flex-column expiry">		  <?php p($l->t('Expires')); ?></div>
							<div class="flex-column participants">	<?php p($l->t('participated')); ?></div>
						</div>
					</div>
				 </div>
			</div>

	<?php foreach ($polls as $poll) : ?>

		<?php
		if (!userHasAccess($poll, $userId)) {
			continue;
		}
		// direct url to poll
		$pollUrl = $urlGenerator->linkToRouteAbsolute('polls.page.goto_poll', array('hash' => $poll->getHash()));
		$owner = $poll->getOwner();

		$expiry_style = '';
		$participated = $_['votes'];
		$participated_class = 'no';
		$participated_title = 'You did not vote';
		$participated_count = count($participated);

		$comments = $_['comments'];
		$commented_class = 'no';
		$commented_title = 'You did not comment';
		$commented_count = count($comments);

		$owner = \OC_User::getDisplayName($owner);


		$timestamp_style = '';
		$expiry_style = ' endless';
		$expiry_date = $l->t('Never');

		if ($poll->getExpire() !== null) {
			$expiry_date = Template::relative_modified_date(strtotime($poll->getExpire())); // does not work, because relative_modified_date seems not to recognise future time diffs
			$expiry_style = ' progress';
			$timestamp_style = ' live-relative-timestamp';
			if (date('U') > strtotime($poll->getExpire())) {
				$expiry_date = Template::relative_modified_date(strtotime($poll->getExpire()));
				$expiry_style = ' expired';
			}
		}

		for ($i = 0; $i < count($participated); $i++) {
			if ($poll->getId() === $participated[$i]->getPollId()) {
				$participated_class = 'yes';
				$participated_title = 'You voted in this poll';
				array_splice($participated, $i, 1);
				break;
			}
		}

		for ($i = 0; $i < count($comments); $i++) {
			if ($poll->getId() === $comments[$i]->getPollId()) {
				$commented_class = 'yes';
				$commented_title = 'You commented this poll';
				array_splice($comments, $i, 1);
				break;
			}
		}
		?>
			<div class="table-row table-body">
				<div class="wrapper group-master">
					<div class="wrapper group-1">
						<div class="thumbnail <?php p($expiry_style . ' commented_' . $commented_class . ' partic_' . $participated_class); ?>"></div><!-- Image to display the status or type of poll -->
						<a href="<?php p($pollUrl); ?>" class="wrapper group-1-1">
							<div class="flex-column name">						  <?php p($poll->getTitle()); ?></div>
							<div class="flex-column description">				   <?php p($poll->getDescription()); ?></div>
						</a>
						<div class="flex-column actions">
							<div class="icon-more popupmenu" value="<?php p($poll->getId()); ?>" id="expand_<?php p($poll->getId()); ?>"></div>
							<div class="popovermenu bubble menu hidden" id="expanddiv_<?php p($poll->getId()); ?>">
								<ul>
									<li>
										<a class="menuitem alt-tooltip copy-link has-tooltip action permanent" data-toggle="tooltip" data-clipboard-text="<?php p($pollUrl); ?>" title="<?php p($l->t('Click to get link')); ?>" href="#">
											<span class="icon-clippy"></span>
											<span>
												<?php p($l->t('Copy Link')); ?>
											</span>
										</a>
									</li>
		<?php if (($poll->getOwner() === $userId) || $adminMode) : ?>
									<li>
										<a id="id_del_<?php p($poll->getId()); ?>" class="menuitem alt-tooltip delete-poll action permanent" data-value="<?php p($poll->getTitle()); ?>" href="#">
											<span class="icon-delete"></span>
											<span>
												<?php p($l->t('Delete poll'));
													if (($poll->getOwner() !== $userId) && $adminMode) {
														p(' (') & p($l->t('as admin')) & p(')');
													}
												?>
											</span>
										</a>
									</li>
									<li>
										<a id="id_edit_<?php p($poll->getId()); ?>" class="menuitem action permanent" href="<?php p($urlGenerator->linkToRoute('polls.page.edit_poll', ['hash' => $poll->getHash()])); ?>">
											<span class="icon-rename"></span>
											<span>
												<?php p($l->t('Edit poll'));
													if (($poll->getOwner() !== $userId) && $adminMode) {
														p(' (') & p($l->t('as admin')) & p(')');
													}
												?>
											</span>
										</a>
									</li>
		<?php endif; ?>
								</ul>

							</div>
						</div>
					</div>
					<div class="wrapper group-2">
						<div class="flex-column owner">
							<div class="avatardiv" title="<?php p($poll->getOwner()); ?>" style="height: 32px; width: 32px;"></div>
							<div class="name-cell"><?php p(\OC_User::getDisplayName($owner)); ?></div>
						</div>
						<div class="wrapper group-2-1">
							<div class="flex-column access"><?php p($l->t($poll->getAccess())); ?></div>
							<div class="flex-column created has-tooltip live-relative-timestamp" data-timestamp="<?php p(strtotime($poll->getCreated()) * 1000); ?>" data-value="<?php p($poll->getCreated()); ?>"><?php p(Template::relative_modified_date(strtotime($poll->getCreated()))); ?></div>
						</div>
						<div class="wrapper group-2-2">
							<div class="flex-column has-tooltip expiry<?php p($expiry_style . $timestamp_style); ?>" data-timestamp="<?php p(strtotime($poll->getExpire()) * 1000); ?>" data-value="<?php p($poll->getExpire()); ?>"> <?php p($expiry_date); ?></div>
							<div class="flex-column participants">
								<div class="symbol alt-tooltip partic_voted icon-<?php p($participated_class); ?>" title="<?php p($participated_title); ?>"></div>
								<div class="symbol alt-tooltip partic_commented icon-comment-<?php p($commented_class); ?>" title="<?php p($commented_title); ?>"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
	<?php endforeach; ?>
		</div>
	</div>
	<form id="form_delete_poll" name="form_delete_poll" action="<?php p($urlGenerator->linkToRoute('polls.page.delete_poll')); ?>" method="POST"></form>
<?php endif; ?>


<?php
// ---- helper functions ----
// from spreed.me
/**
 * @param string $userId
 * @return \OCP\IGroup[]
 */
function getGroups($userId) {
	if (class_exists('\OC_Group')) {
		// Nextcloud <= 11, ownCloud
		return \OC_Group::getUserGroups($userId);
	}
	// Nextcloud >= 12
	$groups = \OC::$server->getGroupManager()->getUserGroups(\OC::$server->getUserSession()->getUser());
	return array_map(function($group) {
		return $group->getGID();
	}, $groups);
}

/**
 * @param OCA\Polls\Db\Event $poll
 * @param string $userId
 * @return boolean
 */
function userHasAccess(OCA\Polls\Db\Event $poll, $userId) {
	if ($poll === null) {
		return false;
	}
	$access = $poll->getAccess();
	if (!User::isLoggedIn()) {
		return false;
	}
	if ($access === 'public' || $access === 'hidden' || $access === 'registered') {
		return true;
	}
	if ($poll->getOwner() === $userId) {
		return true;
	}

	$user_groups = getGroups($userId);
	$arr = explode(';', $access);

	foreach ($arr as $item) {
		if (strpos($item, 'group_') === 0) {
			$grp = substr($item, 6);
			foreach ($user_groups as $user_group) {
				if ($user_group === $grp) {
					return true;
				}
			}
		} else if (strpos($item, 'user_') === 0) {
			$usr = substr($item, 5);
			if ($usr === $userId) {
				return true;
			}
		}
	}
	return false;
}
?>
