<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php if (!empty($showMore)): ?>
    <a href="<?php echo $this->container->get('router')->generate('mautic_point_index', array('search' => $searchString)); ?>" data-toggle="ajax">
        <span><?php echo $view['translator']->trans('mautic.core.search.more', array("%count%" => $remaining)); ?></span>
    </a>
</div>
<?php else: ?>
<?php if ($canEdit): ?>
<a href="<?php echo $this->container->get('router')->generate('mautic_point_action', array('objectAction' => 'edit', 'objectId' => $item->getId())); ?>" data-toggle="ajax">
    <?php echo $item->getName();?>
</a>
<?php else: ?>
<span><?php echo $item->getName(); ?></span>
<?php endif; ?>
<?php endif; ?>