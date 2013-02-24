<?php
/*
 * Xibo - Digitial Signage - http://www.xibo.org.uk
 * Copyright (C) 2006-2013 Daniel Garner
 *
 * This file is part of Xibo.
 *
 * Xibo is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version. 
 *
 * Xibo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Xibo.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Theme variables:
 *  form_id = The ID of the Form
 * 	form_action = The URL for calling the Add Transaction
 *  displaygroup = The Name of the Display Group
 *  description = The Description of the Display Group
 */
defined('XIBO') or die("Sorry, you are not allowed to directly access this page.<br /> Please press the back button in your browser.");
?>
<form id="<?php echo Theme::Get('form_id'); ?>" class="XiboForm" method="post" action="<?php echo Theme::Get('form_action'); ?>">
    <?php echo Theme::Get('form_meta'); ?>
	<table>
        <tr>
            <td><label for="group" title="<?php echo Theme::Translate('The Name for this Group'); ?>"><?php echo Theme::Translate('Name'); ?></label></td>
            <td><input class="required" type="text" name="group" value="<?php echo Theme::Get('displaygroup'); ?>" maxlength="50"></td>
        </tr>
        <tr>
            <td><label for="desc" title="<?php echo Theme::Translate('A short description of this Group'); ?>"><?php echo Theme::Translate('Description'); ?></label></td>
            <td><input type="text" name="desc" value="<?php echo Theme::Get('description'); ?>" maxlength="254"></td>
        </tr>
    </table>
</form>