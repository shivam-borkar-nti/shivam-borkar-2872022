<?php
if (!defined('WPINC')) {
    die('Closed');
}
if(defined('REGMAGIC_ADDON')) include_once(RM_ADDON_ADMIN_DIR . 'views/template_rm_field_manager_new.php'); else {
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
add_thickbox();
$allowed_c_fields = RM_Utilities::get_allowed_conditional_fields();
$primary_fields = array();
?>

<div class="rm-form-builder-navbar">
    <div class="rm-form-builder-navbar-wrap">
        <div class="rm-form-builder-top-box">
            <div class="rm-form-builder-title rm-form-builder-action"><?php _e('Fields Manager','custom-registration-form-builder-with-submission-manager'); ?></div>
            <div class="rm-form-builder-views rm-form-builder-action">
                <ul>
                    <?php
                    $design_link_class = $design_link_tooltip = "";
                    if($data->theme == 'classic') {
                        $design_link_class = "class='rm_deactivated'";
                        $design_link_tooltip = __('Form design customization is not applicable for Classic theme. To enable please change theme in Global Settings >> General Settings.', 'custom-registration-form-builder-with-submission-manager');
                    }
                    ?>
                    <li title="<?php echo esc_attr($design_link_tooltip); ?>"><a <?php echo wp_kses_post($design_link_class); ?> href="?page=rm_form_sett_view&rdrto=rm_field_manage&rm_form_id=<?php echo esc_attr($data->form_id); ?>"><span class="material-icons"> palette </span><?php _e('Design','custom-registration-form-builder-with-submission-manager'); ?></a></li>
                    <li><a id="rm_form_preview_action" class="thickbox rm_form_preview_btn" href="<?php echo add_query_arg(array('form_prev' => '1','form_id' => $data->form_id), get_permalink($data->prev_page)); ?>&TB_iframe=true&width=900&height=600"><span class="material-icons"> preview </span><?php _e('Preview','custom-registration-form-builder-with-submission-manager'); ?></a></li>
                </ul>
            </div>
            <div class="rm-form-builder-form-toggle rm-form-builder-action">
                <?php echo wp_kses_post(RM_UI_Strings::get('LABEL_TOGGLE_FORM')); ?>
                <select id="rm_form_dropdown" name="form_id" onchange = "rm_load_page(this, 'field_manage')">
                    <?php
                    echo "<option value='rm_login_form'>Login Form</option>";
                    foreach ($data->forms as $form_id => $form)
                        if ($data->form_id == $form_id)
                            echo "<option value=".esc_attr($form_id)." selected>".esc_html($form)."</option>";
                        else
                            echo "<option value=".esc_attr($form_id).">".esc_html($form)."</option>";
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>

<?php
if($data->total_page > 1)
    echo "<div class='rm-builder-notice'><div class='rmnotice'>".wp_kses_post(RM_UI_Strings::get('MULTIPAGE_DEGRADE_WARNING'))."</div></div>";
?>

<div class="rmagic rm-field-manager-main">
    <div id="rm-form-builder" class="rm-form-builder-main">
        <div id="rm-form-page-id-1" class="rm-form-builder-box">
            <!--
            <div class="rm-form-page-actions">
                <div class="rm-form-page-edit rm-form-page-action" title="Edit Page" ><a href="#"><span class="material-icons">create</span></a></div>
                <div class="rm-form-page-delete rm-form-page-action" title="Delete Page"><a href="#"><span class="material-icons">delete</span></a></div>
            </div>
            -->
            <ul class="rm_sortable_form_rows" id="rm-field-sortable">
                   
            <?php $is_privacy_added = 0; foreach($data->rows_data as $row_order => $row) { ?>
             
            <li class="rm-fields-row <?php echo 'rm-fields-' . str_replace(':', '-', $row->columns); ?>" id="<?php echo esc_attr($row->row_id); ?>">
                <div class="rm-field-move rm_sortable_handle"><span class="rm-drag-sortable-handle"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 20 20"><path d="M2 11h16v2H2zm0-4h16v2H2zm8 11l3-3H7l3 3zm0-16L7 5h6l-3-3z"/></svg></span></div>
                <div class="rm-field-row-actions">
                    <div class="rm-field-row-setting rm-field-row-action" title="Row Setting" ><a onclick="CallModalBox(this)" data-row-id="<?php echo esc_attr($row->row_id); ?>" data-action="update_row" data-row-columns="<?php echo esc_attr($row->columns); ?>" data-row-class="<?php echo esc_attr($row->class); ?>" data-row-gutter="<?php echo esc_attr($row->gutter); ?>" data-row-bmargin="<?php echo esc_attr($row->bmargin); ?>" data-row-width="<?php echo esc_attr($row->width); ?>" data-row-heading="<?php echo esc_attr($row->heading); ?>" data-row-subheading="<?php echo esc_attr($row->subheading); ?>" data-page-no="<?php echo esc_attr($row->page_no); ?>"><span class="material-icons">settings</span></a></div>
                    <div class="rm-field-row-delete rm-field-row-action" title="Delete Row"><a onclick="CallRowDeleteBox(this)" data-form-id="<?php echo esc_attr($data->form_id); ?>" data-row-id="<?php echo esc_attr($row->row_id); ?>"><span class="material-icons">delete</span></a></div>
                </div>
                <?php foreach ($row->fields as $field_order => $field) { ?>
                <?php if (!empty($field)) {
                        $is_privacy_added = 0;
                        if($field->field_type=='Privacy') {
                            $is_privacy_added = 1;
                        }
                        $f_options = maybe_unserialize($field->field_options);
                        if (isset($f_options->field_is_multiline) && $f_options->field_is_multiline == 1) {
                            $field->field_type = $field->field_type . '_M';
                        }
                        if($field->is_field_primary) {
                            array_push($primary_fields, $field->field_type);
                        }
                ?>
                <div class="rm-form-field<?php echo ($row->columns == '2:1' && $field_order == 0) ? ' rm-2-col' : ' rm-1-col'; ?>" rm-grid-id="<?php echo esc_attr($field_order) + 1; ?>">
                    <div class="rm-field-box-name"><?php echo esc_html($data->field_types[$field->field_type]); ?></div>
                    <div class="rm-field-box-label"><?php echo esc_html($field->field_label); ?></div>
                    <div class="rm-field-actions">
                        <div class="rm-field-rules rm-field-action">
                            <?php
                            if (empty($field->is_field_primary) && in_array($field->field_type, $allowed_c_fields)):
                                $c_count = '';
                                if (isset($f_options->conditions) && isset($f_options->conditions['rules']) && count($f_options->conditions['rules']) > 0) {
                                    $c_count = '' . count($f_options->conditions['rules']) . '';
                                }
                            ?>
                            <a href="javascript:void(0)" onClick="showConditionFormModal(<?php echo esc_attr($field->field_id); ?>)"><span class="material-icons">rule</span><span class="rm-conditions-badge"><?php echo esc_html($c_count); ?></span></a>
                            <?php endif; ?>
                        </div>
                        <div class="rm-field-setting rm-field-action" title="Field Setting"><a onclick="edit_field_in_page('<?php echo esc_attr($field->field_type); ?>',<?php echo esc_attr($field->field_id); ?>,<?php echo esc_attr($field->page_no); ?>)" href="javascript:void(0)"><span class="material-icons">settings</span></a></div>
                        <div class="rm-field-delete rm-field-action" title="Delete Field">
                            <?php if ($field->is_field_primary == 1 && empty($field->is_deletion_allowed) && strtolower($field->field_type)=="username"): ?>
                            <a onclick="CallFieldDeleteBox(this)" class="rm-premium-option"><span class="material-icons">delete</span></a>
                            <div class="rm-premium-option-popup" style="display:none">
                                <span class="rm-premium-option-popup-close rm-premium-option" onclick="CallFieldDeleteBox(this)">×</span>
                                <span class="rm-premium-option-popup-nub"></span>
                                <span class="rm_buy_pro_inline"><?php printf(__('To unlock removing Username field (and many more features), please upgrade <a href="%s" target="blank">Click here</a>', 'custom-registration-form-builder-with-submission-manager'), RM_Utilities::comparison_page_link()); ?> </span>
                            </div>
                            <div class="rm-premium-option-popup-overlay rm-premium-option" onclick="CallFieldDeleteBox(this)" style="display:none"></div>
                            <?php elseif ($field->is_field_primary == 1 && empty($field->is_deletion_allowed)) : ?>
                            <a href="javascript:void(0)" class="rm_deactivated" onclick="CallFieldDeleteBox(this)"><span class="material-icons">delete</span></a>
                            <?php else: ?>
                            <a onclick="CallFieldDeleteBox(this)" data-form-id="<?php echo esc_attr($data->form_id); ?>" data-field-id="<?php echo esc_attr($field->field_id); ?>" data-field-type="<?php echo esc_attr($field->field_type); ?>" data-row-id="<?php echo esc_attr($row->row_id); ?>" data-order="<?php echo esc_attr($field_order); ?>"><span class="material-icons">delete</span></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php } else { ?>
                <div class="rm-form-field<?php echo ($row->columns == '2:1' && $field_order == 0) ? ' rm-2-col' : ' rm-1-col'; ?>">
                    <div class="rm-insert-field">
                        <button class="rm-insert-field-button">
                            <a href="#rm-field-selector" onclick="CallFieldModalBox(this)" data-page-no="<?php echo esc_attr($row->page_no); ?>" data-row-id="<?php echo esc_attr($row->row_id); ?>" data-order="<?php echo esc_attr($field_order); ?>"><?php _e('Add Field', 'custom-registration-form-builder-with-submission-manager'); ?> <span class="material-icons">add_box</span></a>
                        </button>
                    </div>
                </div>
                <?php } } ?>
            </li>
            <?php } ?>
                               
            </ul>
            <!-- Begin: New Field -->
            <div class="rm-insert-row">
                <button class="rm-insert-row-button">
                    <a href="#rm-field-selector" onclick="CallFieldModalBox(this)" data-page-no="<?php echo esc_attr($row->page_no); ?>" data-row-id="0" data-order="0"><?php _e('Add Field', 'custom-registration-form-builder-with-submission-manager'); ?> <span class="material-icons">add_box</span></a>
                </button>
            </div>
            
             <!-- Ends: New Field -->
            
            <!-- Begin: Submit Field -->
            <?php 
                $submit_label = ($data->form_options->form_submit_btn_label) ? $data->form_options->form_submit_btn_label : __('Submit', 'custom-registration-form-builder-with-submission-manager');
                $prev_label = ($data->form_options->form_prev_btn_label) ? $data->form_options->form_prev_btn_label : RM_UI_Strings::get('LABEL_PREV_FORM_PAGE');
                $next_label = ($data->form_options->form_next_btn_label) ? $data->form_options->form_next_btn_label : __('Next', 'custom-registration-form-builder-with-submission-manager');
                $btn_align = ($data->form_options->form_btn_align) ? $data->form_options->form_btn_align : "center";
                $ralign_check_state = $lalign_check_state = $calign_check_state = "";
                if($btn_align === "right")
                    $ralign_check_state = "checked";
                else if($btn_align === "left")
                    $lalign_check_state = "checked";
                else
                    $calign_check_state = "checked";

                $hideprev_check_state = (isset($data->form_options->no_prev_button) && $data->form_options->no_prev_button) ? 'checked': "";
            ?>
            <div class="rm-field-submit-field-holder">
                <div class="rm-field-submit-field">
                    <div class="rm-field-submit-field-btn-container rm-field-btn-align-<?php echo esc_attr($btn_align); ?>">
                        &#8203;<!-- Zero width space character is added to workaround webkit bug where clicking outside the div enables editing of the content. -->

                        <div class="rm-field-sub-btn rm_field_btn" id="rm_field_sub_button" title="<?php _e('Click to edit button label', 'custom-registration-form-builder-with-submission-manager') ?>" contenteditable="true" spellcheck="false"><?php echo wp_kses_post($submit_label); ?></div>
                        &#8203;
                    </div>
                    <div class="rm-field-submit-field-options">
                        <div class="rm-field-submit-field-option-row rm-field-submit-hide-prev">&nbsp;</div>
                        <div class="rm-field-submit-field-option-row rm-field-submit-alignment">
                            <input type="radio" name="rm_field_submit_field_align" value="left" id="rm_field_submit_field_align_left" <?php echo esc_attr($lalign_check_state); ?> ><label for="rm_field_submit_field_align_left"><?php _e('Left','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <input type="radio" name="rm_field_submit_field_align" value="center" id="rm_field_submit_field_align_center" <?php echo esc_attr($calign_check_state); ?> ><label for="rm_field_submit_field_align_center"><?php _e('Center','custom-registration-form-builder-with-submission-manager'); ?></label>
                            <input type="radio" name="rm_field_submit_field_align" value="right" id="rm_field_submit_field_align_right" <?php echo esc_attr($ralign_check_state); ?> ><label for="rm_field_submit_field_align_right"><?php _e('Right','custom-registration-form-builder-with-submission-manager'); ?></label>
                        </div>
                        <div class="rm-field-submit-field-option-row rm-field-submit-ajax-loader" style="visibility: hidden">
                            <?php _e('Updating...','custom-registration-form-builder-with-submission-manager'); ?>
                        </div>
                    </div>
                </div>
                <div class="rm-field-submit-field-hint"><?php _e('Click on submit button to edit label','custom-registration-form-builder-with-submission-manager'); ?></div>
            </div>
            <!-- End: Submit Field -->
            
              <!-- <div class="rm-form-buttons-row">
                <div class="rm-form-buttons-holder">
                    <div class="rm-form-button-field rm-submit-field-btn"><?php _e('Submit','custom-registration-form-builder-with-submission-manager'); ?></div>
                     <div class="rm-form-buttons-setting"><a href="javascript:void(0)" onclick="CallFormButtonSettings(this)"><span class="material-icons">settings</span></a></div>
                </div>
            </div> -->
            
        </div>
        <!--- Insert Page 2
        <div class="rm-insert-new-form-page">
            <button class="rm-insert-new-form-page-button">
                Add Page <span class="material-icons">add_box</span> </button>
        </div>
        Page 2 End --->
    </div>
    <!-- Row Setting Popup -->
    <div id="rm-field-row-setting-modal" class="rm-modal-view" style="display: none;">
        <form method="post" action="" id="rm-row-add-edit-form">
        <div class="rm-modal-overlay rm-field-popup-overlay-fade-in"></div>
        <div class="rm_field_row_setting_wrap rm-select-row-setting rm-field-popup-out">
            <div class="rm-modal-titlebar rm-new-form-popup-header">
                <div class="rm-modal-title">
                    <?php _e('Row Properties','custom-registration-form-builder-with-submission-manager'); ?>
                </div>
                <span class="rm-modal-close">×</span>
            </div>
            <div class="rm-modal-container">
                <div class="rm-field-row-wrap">
                    
                    <div class="rmrow rm-field-head-row">
                      <div class="rm-field-columns-head">
                                <div class="rm-field-column-label"><?php _e('Heading (Optional)','custom-registration-form-builder-with-submission-manager'); ?></div>
                                <input type="text" placeholder="Heading" id="rm-row-heading" class="rm-form-column-control" name="heading" value="">
                                <div class="rm-form-column-help-text"><?php _e('Heading text for the fields in this row. Rendered on frontend with larger font size.','custom-registration-form-builder-with-submission-manager'); ?></div>
                        </div>
                        
                        <div class="rm-field-columns-head">
                                <div class="rm-field-column-label"><?php _e('Sub-heading (Optional)','custom-registration-form-builder-with-submission-manager'); ?></div>
                                <input type="text" placeholder="Sub Heading" id="rm-row-subheading" class="rm-form-column-control" name="subheading" value="">
                                <div class="rm-form-column-help-text"><?php _e('Subtitle for the fields in this row. Rendered on frontend with muted body font.','custom-registration-form-builder-with-submission-manager'); ?></div>
                        </div> 
                        
                    </div>
                    
                    <div class="rmrow">
                        <div class="rm-fields-columns-wrap">
                            <div class="rm-fields-columns">
                                <h3><?php _e('Field Columns','custom-registration-form-builder-with-submission-manager'); ?></h3>
                                <ul>
                                    <li>
                                        <div class="rm-fields-column">
                                            <span style="width: 100%"></span>
                                        </div>
                                        <label> 
                                            <input type="radio" class="rm-field-radio" value="1" name="columns" data-allowed-fields="1" onclick="rmColumnSelector(this)" /><?php _e('Single','custom-registration-form-builder-with-submission-manager'); ?></label>
                                    </li>
                                    <li>
                                        <div class="rm-fields-column">
                                            <span style="width: 50%"></span>
                                            <span style="width: 50%"></span>
                                        </div>
                                        <label>
                                            <input type="radio" class="rm-field-radio" value="1:1" name="columns" data-allowed-fields="2" onclick="rmColumnSelector(this)" /><?php _e('1:1','custom-registration-form-builder-with-submission-manager'); ?></label>
                                    </li>
                                    <li>
                                        <div class="rm-fields-column">
                                            <span style="width: 75%"></span>
                                            <span style="width: 25%"></span>
                                        </div>
                                        <label>
                                            <input type="radio" class="rm-field-radio" value="2:1" name="columns" data-allowed-fields="2" onclick="rmColumnSelector(this)" /><?php _e('2:1','custom-registration-form-builder-with-submission-manager'); ?></label>
                                    </li>
                                    <li>
                                        <div class="rm-fields-column">
                                            <span style="width: 33%"></span>
                                            <span style="width: 33%"></span>
                                            <span style="width: 33%"></span>
                                        </div>
                                        <label>
                                            <input type="radio" class="rm-field-radio" value="1:1:1" name="columns" data-allowed-fields="3" onclick="rmColumnSelector(this)" /><?php _e('1:1:1','custom-registration-form-builder-with-submission-manager'); ?></label>
                                    </li>
                                    <li>
                                        <div class="rm-fields-column">
                                            <span style="width: 25%"></span>
                                            <span style="width: 25%"></span>
                                            <span style="width: 25%"></span>
                                            <span style="width: 25%"></span>
                                        </div>
                                        <label>
                                            <input type="radio" class="rm-field-radio" value="1:1:1:1" name="columns" data-allowed-fields="4" onclick="rmColumnSelector(this)"/><?php _e('1:1:1:1','custom-registration-form-builder-with-submission-manager'); ?></label>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="rmrow">
                        <h3><?php _e('Optional styling:','custom-registration-form-builder-with-submission-manager'); ?></h3>
                    </div>
                    <div class="rmrow">
                        <div class="rm-field-columns-setting">
                            <div class="rm-field-columns">
                                <div class="rm-field-column-head"><?php _e('CSS Class','custom-registration-form-builder-with-submission-manager'); ?></div>
                                <input type="text" placeholder="E.g. row" id="rm-row-css-class" class="rm-form-column-control" name="class" value="">
                                <div class="rm-form-column-help-text"><?php _e('Add additional CSS class to the row for custom styling.','custom-registration-form-builder-with-submission-manager'); ?></div>
                            </div>
                            <div class="rm-field-columns">
                                <div class="rm-field-column-head"><?php _e('Gutter','custom-registration-form-builder-with-submission-manager'); ?></div>
                                <input type="number" placeholder="E.g. 24" id="rm-column-gutter" class="rm-form-column-control" name="gutter" value="" min="0" max="30">
                                <span>px</span>
                                <div class="rm-form-column-help-text"><?php _e('Define spacing between columns in this row in px. Does not applies to single column layouts.','custom-registration-form-builder-with-submission-manager'); ?></div>
                            </div>
                            <div class="rm-field-columns">
                                <div class="rm-field-column-head"><?php _e('Bottom Margin','custom-registration-form-builder-with-submission-manager'); ?></div>
                                <input type="number" placeholder="E.g. 48" id="rm-column-bmargin" class="rm-form-column-control" name="bmargin" value="" min="0"> 
                                <span>px</span>
                                <div class="rm-form-column-help-text"><?php _e('Add additional vertical spacing between this row, and the the row just below, in px.','custom-registration-form-builder-with-submission-manager'); ?></div>
                            </div>
                            <div class="rm-field-columns">
                                <div class="rm-field-column-head"><?php _e('Max Width','custom-registration-form-builder-with-submission-manager'); ?></div>
                                <input type="number" placeholder="E.g. 800" id="rm-column-width" class="rm-form-column-control" name="width" value="" min="0" max="1500"> 
                                <span>px</span>
                                <div class="rm-form-column-help-text"><?php _e('Define maximum width for this row in px. Leave empty for full-width (justified).','custom-registration-form-builder-with-submission-manager'); ?></div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="form-id" value="<?php echo esc_attr($data->form_id); ?>">
                    <input type="hidden" name="page-no" value="1">
                    <input type="hidden" name="rm_row_id" value="-1">
                    <input type="hidden" name="rm_action" value="add_row">
                    <div class="rm-form-builder-modal-footer">
                        <div class="rm-cancel-row-setting"><a href="javascript:void(0)" class="rm-modal-close">← &nbsp;<?php _e('Cancel','custom-registration-form-builder-with-submission-manager'); ?></a></div>
                        <div class="rm-save-row-setting"><input type="submit" value="Save" class="rm-delete-row-button"></div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
    <!-- Row Setting Popup End -->  
    <!-- Row Delete Popup -->
    <div id="rm-field-row-delete-modal" class="rm-modal-view" style="display: none;">
        <div class="rm-modal-overlay rm-field-popup-overlay-fade-in"></div>
        <div class="rm_field_row_setting_wrap rm-select-row-setting rm-field-popup-out">
            <div class="rm-modal-titlebar rm-new-form-popup-header">
                <div class="rm-modal-title">
                    <?php _e('Delete Row','custom-registration-form-builder-with-submission-manager'); ?>
                </div>
                <span class="rm-modal-close">×</span>
            </div>
            <div class="rm-modal-container">
                <div class="rmrow">
                    <div class="rm-delete-row-info-icon">
                        <span class="material-icons">error</span>
                    </div>
                </div>
                <div class="rmrow">
                    <div class="rm-delete-row-info-text">
                        <?php _e('Are you sure you want to delete this row?','custom-registration-form-builder-with-submission-manager'); ?>
                    </div>
                </div>
                <div class="rm-form-builder-modal-footer">
                    <div class="rm-cancel-delete-action"><a href="javascript:void(0)" class="rm-modal-close">← &nbsp;<?php _e('Cancel','custom-registration-form-builder-with-submission-manager'); ?></a></div>
                    <div class="rm-confirm-delete-action"><a id="rm-delete-row-link"><?php _e('Delete','custom-registration-form-builder-with-submission-manager'); ?></a></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Row Delete Popup End -->
    <!-- Field Delete Popup -->
    <div id="rm-field-delete-modal" class="rm-modal-view" style="display: none;">
        <div class="rm-modal-overlay rm-field-popup-overlay-fade-in"></div>
        <div class="rm_field_row_setting_wrap rm-select-row-setting rm-field-popup-out">
            <div class="rm-modal-titlebar rm-new-form-popup-header">
                <div class="rm-modal-title" id="rm-field-delete-modal-title">
                    <?php _e('Delete Field','custom-registration-form-builder-with-submission-manager'); ?>
                </div>
                <span class="rm-modal-close">×</span>
            </div>
            <div class="rm-modal-container">
                <div class="rmrow">
                    <div class="rm-delete-row-info-icon">
                        <span class="material-icons">error</span>
                    </div>
                </div>
                <div class="rmrow">
                    <div class="rm-delete-row-info-text" id="rm-field-delete-modal-info">
                        <?php _e('Are you sure you want to delete this field?','custom-registration-form-builder-with-submission-manager'); ?>
                    </div>
                </div>
                <div class="rm-form-builder-modal-footer">
                    <div class="rm-cancel-delete-action"><a href="javascript:void(0)" class="rm-modal-close">← &nbsp;<?php _e('Cancel','custom-registration-form-builder-with-submission-manager'); ?></a></div>
                    <div class="rm-confirm-delete-action"><a id="rm-delete-field-link" href="javascript:void(0)"><?php _e('Delete','custom-registration-form-builder-with-submission-manager'); ?></a></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Field Delete Popup End -->

    <!--- Field Selector PopUp -->
    <div id="rm-field-selector" class="rm-modal-view" style="display:none">
        <div class="rm-modal-overlay"></div> 

        <div class="rm-modal-wrap">
            <div class="rm-modal-titlebar">
                <div class="rm-modal-title"> <?php _e('Choose a field type','custom-registration-form-builder-with-submission-manager'); ?></div>
                <span  class="rm-modal-close">&times;</span>
            </div>
            <div class="rm-modal-container">
            <div class="rmrow">
                <div class="rm-field-selector">
                    <?php require RM_ADMIN_DIR."views/template_rm_field_picker.php"; ?>
                </div>
            </div>
            </div>
        </div>
    </div>
    <!---End Field Selector PopUp -->
    
    <!--- Widget Selector PopUp -->
    <div id="rm-widget-selector" class="rm-modal-view" style="display:none">
        <div class="rm-modal-overlay"></div> 

        <div class="rm-modal-wrap">
            <div class="rm-modal-titlebar">
                <div class="rm-modal-title"><?php _e('MagicWidgets', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                <span  class="rm-modal-close">&times;</span>
            </div>
            <div class="rm-modal-container">
                <div class="rmrow">
                    <div class="rm-widget-selector">
                        <?php require RM_ADMIN_DIR . "views/template_rm_widget_picker.php"; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!---End Widget Selector PopUp -->
    
    <!-- User Name Row Delete Popup -->
        
    <div id="rm-username-delete-row" class="rm-modal-view" style="display: none;">
        <div class="rm-modal-overlay rm-field-popup-overlay-fade-in"></div>
        <div class="rm_field_row_setting_wrap rm-select-row-setting rm-field-popup-out">
            <div class="rm-modal-titlebar rm-new-form-popup-header">
                <div class="rm-modal-title">
                    <?php _e('Delete Row', 'custom-registration-form-builder-with-submission-manager'); ?>
                </div>
                <span class="rm-modal-close">×</span>
            </div>
            <div class="rm-modal-container">
                <div class="rmrow">
                    <div class="rm-delete-row-info-icon">
                        <span class="material-icons">error</span>
                    </div>
                </div>
                <div class="rmrow">
                    <div class="rm-delete-row-info-text">
                    <?php _e('You cannot delete a row that has either the Username or the Password field in it.','custom-registration-form-builder-with-submission-manager'); ?>
                    </div>
                </div>
                <!--
                <div class="rm-form-builder-modal-footer">
                    <div class="rm-confirm-delete-action"><a id="rm-delete-row-link">Yes, Remove Username</a></div>
                    <div class="rm-cancel-delete-action"><a id="rm-delete-row-link">No, Keep Username</a></div>
                </div>
                -->
            </div>
        </div>
    </div>
        
    <!-- Row Delete Popup End -->
    
    
    
    
    <!-- Form Button Setting Popup Popup -->

<!--    <div id="rm-form-button-settings-modal" class="rm-modal-view" style="display: none;">
            <div class="rm-modal-overlay rm-field-popup-overlay-fade-in"></div>

            <div class="rm_field_row_setting_wrap rm-select-row-setting rm-field-popup-out">
                <div class="rm-modal-titlebar rm-new-form-popup-header">
                    <div class="rm-modal-title"><?php _e('Styling & Buttons', 'custom-registration-form-builder-with-submission-manager'); ?></div>
                    <span class="rm-modal-close">×</span>
                </div>
                <div class="rm-modal-container">


                    <div class="rmrow">

                        <label for="rm-field-custom-submit-text" class="rm-label"><?php _e('Button text', 'custom-registration-form-builder-with-submission-manager'); ?></label>
                        <input type="text" placeholder="Enter text" id="rm-field-custom-submit-text" class="rm-form-control" value="Submit">

                    </div>

                    <div class="rmrow">
                        <label for="rm-field-custom-submit-text" class="rm-label"><?php _e('Button align', 'custom-registration-form-builder-with-submission-manager'); ?></label>

                        <div class="rm-field-submit-field-option-row rm-field-submit-alignment">
                            <input type="radio" name="rm_field_submit_field_align" value="left" id="rm_field_submit_field_align_left"><label for="rm_field_submit_field_align_left"><?php _e('Left', 'custom-registration-form-builder-with-submission-manager'); ?></label>
                            <input type="radio" name="rm_field_submit_field_align" value="center" id="rm_field_submit_field_align_center"><label for="rm_field_submit_field_align_center"><?php _e('Center', 'custom-registration-form-builder-with-submission-manager'); ?></label>
                            <input type="radio" name="rm_field_submit_field_align" value="right" id="rm_field_submit_field_align_right" checked=""><label for="rm_field_submit_field_align_right"><?php _e('Right', 'custom-registration-form-builder-with-submission-manager'); ?></label>
                        </div>
                    </div>



                    <div class="rm-form-builder-modal-footer">
                        <div class="rm-discard-setting"><a href="javascript:void(0)">← &nbsp;<?php _e('Cancel', 'custom-registration-form-builder-with-submission-manager'); ?></a></div> 
                        <div class="rm-save-setting"><button type="button" class="rm-save-setting-button"><?php _e('Save', 'custom-registration-form-builder-with-submission-manager'); ?></button></div> 
                    </div>

                </div>
            </div>
        </div>-->

<!-- Form Button Setting Popup End -->

<!--- Field Conditions PopUp -->
    <?php
    // Including field condition template
    include RM_ADMIN_DIR."views/template_rm_field_conditions.php";
    ?>
<!--- End Field Conditions PopUp -->
</div>

<script>
    var field_order_in_row = -1;
    var row_id_for_field = -1;
    var curr_form_page_for_field = 1;
    function CallModalBox(ele) {
        var fields_in_this_row = 0;
        jQuery('li#' + jQuery(ele).data('row-id')).find('div.rm-field-box-name').each(function(index) {
            fields_in_this_row++;
        });
        if(fields_in_this_row > 0) {
            jQuery('input[name=columns]').each(function(index) {
                if(jQuery(this).data('allowed-fields') < fields_in_this_row) {
                    jQuery(this).attr("disabled", true);
                } else {
                    jQuery(this).attr("disabled", false);
                }
                
                if(jQuery(this).val() == jQuery(ele).data('row-columns')) {
                    jQuery(this).prop("checked", true);
                } else {
                    jQuery(this).prop("checked", false);
                }
            });
        } else {
            jQuery('input[name=columns]').each(function(index) {
                jQuery(this).removeAttr("disabled");
            });
        }
        jQuery("#rm-field-row-setting-modal").toggle();
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');

        jQuery('.rm-modal-overlay').removeClass('rm-field-popup-overlay-fade-out');
        jQuery('.rm-modal-overlay').addClass('rm-field-popup-overlay-fade-in');
        
        jQuery('input[name=columns][value="' + jQuery(ele).data('row-columns') + '"]').attr('checked',true);
        jQuery('input[name=class]').val(jQuery(ele).data('row-class'));
        jQuery('input[name=gutter]').val(jQuery(ele).data('row-gutter'));
        jQuery('input[name=bmargin]').val(jQuery(ele).data('row-bmargin'));
        jQuery('input[name=width]').val(jQuery(ele).data('row-width'));
        jQuery('input[name=heading]').val(jQuery(ele).data('row-heading'));
        jQuery('input[name=subheading]').val(jQuery(ele).data('row-subheading'));
        jQuery('input[name=page-no]').val(jQuery(ele).data('page-no'));
        jQuery('input[name=rm_row_id]').val(jQuery(ele).data('row-id'));
        jQuery('input[name=rm_action]').val(jQuery(ele).data('action'));
    }
    
    function CallFieldModalBox(ele) {
        jQuery(jQuery(ele).attr('href')).toggle();
        field_order_in_row = jQuery(ele).data('order');
        row_id_for_field = jQuery(ele).data('row-id');
        curr_form_page_for_field = jQuery(ele).data('page-no');
    }
    
    
    function CallFormButtonSettings(ele) {
        jQuery("#rm-form-button-settings-modal").toggle();
        if(jQuery(ele).attr('href')=='#rm-form-button-settings-modal') {
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');

            jQuery('.rm-modal-overlay').removeClass('rm-field-popup-overlay-fade-out');
            jQuery('.rm-modal-overlay').addClass('rm-field-popup-overlay-fade-in');
        }
    }
    
    
    function CallRowDeleteBox(ele) {
        var fieldCheck = false;
        jQuery('li#' + jQuery(ele).data('row-id')).find('div.rm-field-box-name').each(function(index) {
            if(jQuery(this).text() == 'Account Username' || jQuery(this).text() == 'Account Password') { fieldCheck = true; }
        });
        if(fieldCheck) {
            jQuery("#rm-username-delete-row").toggle();
        
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');

            jQuery('.rm-modal-overlay').removeClass('rm-field-popup-overlay-fade-out');
            jQuery('.rm-modal-overlay').addClass('rm-field-popup-overlay-fade-in');
        } else {
            jQuery("#rm-field-row-delete-modal").toggle();

            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');

            jQuery('.rm-modal-overlay').removeClass('rm-field-popup-overlay-fade-out');
            jQuery('.rm-modal-overlay').addClass('rm-field-popup-overlay-fade-in');

            jQuery('#rm-delete-row-link').attr('href', '?page=rm_field_manage&rm_form_id=' + jQuery(ele).data('form-id') + '&rm_row_id=' + jQuery(ele).data('row-id') + '&rm_action=delete_row');
        }
    }


    function CallFieldDeleteBox(ele) {
        if(jQuery(ele).hasClass('rm-premium-option')) {
            jQuery('.rm-premium-option-popup, .rm-premium-option-popup-overlay').toggle();
        } else {
            if(jQuery(ele).data('field-type').toLowerCase() == 'username') {
                jQuery('#rm-field-delete-modal-title').text('<?php _e('Remove Username Field?','custom-registration-form-builder-with-submission-manager'); ?>');
                jQuery('#rm-field-delete-modal-info').text('<?php _e('You are about to remove Username field from this form. Consequently, Email field wil be used as Username field. Registering users can later login using their Email and Password. Do you wish to proceed? ','custom-registration-form-builder-with-submission-manager'); ?>');
            }
            if(jQuery(ele).data('field-type').toLowerCase() == 'userpassword') {
                jQuery('#rm-field-delete-modal-title').text('<?php _e('Remove Password Field?','custom-registration-form-builder-with-submission-manager'); ?>');
                jQuery('#rm-field-delete-modal-info').text('<?php _e('You are about to remove Password field from this form. Password field will be autogenerated and emailed to the user on successful registration. Do you wish to proceed?','custom-registration-form-builder-with-submission-manager'); ?>');
            }
            jQuery("#rm-field-delete-modal").toggle();

            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');

            jQuery('.rm-modal-overlay').removeClass('rm-field-popup-overlay-fade-out');
            jQuery('.rm-modal-overlay').addClass('rm-field-popup-overlay-fade-in');

            jQuery('#rm-delete-field-link').attr('href', '?page=rm_field_manage&rm_form_id=' + jQuery(ele).data('form-id') + '&rm_field_id=' + jQuery(ele).data('field-id') + '&rm_row_id=' + jQuery(ele).data('row-id') + '&rm_order_in_row=' + jQuery(ele).data('order') + '&rm_action=delete');
        }
    }
    
    
    function CallUserNameDeleteRow(ele) {
        jQuery("#rm-username-delete-row").toggle();
        
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-out');
        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-in');

        jQuery('.rm-modal-overlay').removeClass('rm-field-popup-overlay-fade-out');
        jQuery('.rm-modal-overlay').addClass('rm-field-popup-overlay-fade-in');

        jQuery('#rm-delete-field-link').attr('href', '?page=rm_field_manage&rm_form_id=' + jQuery(ele).data('form-id') + '&rm_field_id=' + jQuery(ele).data('field-id') + '&rm_row_id=' + jQuery(ele).data('row-id') + '&rm_order_in_row=' + jQuery(ele).data('order') + '&rm_action=delete');
    }


    jQuery(document).ready(function () {
        jQuery('.rm-modal-close, .rm-modal-overlay').click(function () {
            setTimeout(function () {
                //jQuery(this).parents('.rm-modal-view').hide();
                jQuery('.rm-modal-view').hide();
            }, 400);
            
            jQuery('#rm-field-delete-modal-title').text('<?php _e('Delete Field','custom-registration-form-builder-with-submission-manager'); ?>');
            jQuery('#rm-field-delete-modal-info').text('<?php _e('Are you sure you want to delete this field?','custom-registration-form-builder-with-submission-manager'); ?>');
        });


        jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting .rm-modal-close, #rm-field-row-setting-modal .rm-modal-overlay').on('click', function () {
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').removeClass('rm-field-popup-in');
            jQuery('.rmagic .rm_field_row_setting_wrap.rm-select-row-setting').addClass('rm-field-popup-out');

            jQuery('.rm-modal-overlay').removeClass('rm-field-popup-overlay-fade-in');
            jQuery('.rm-modal-overlay').addClass('rm-field-popup-overlay-fade-out');
        });

        jQuery("body").addClass("registrationmagic-form-builder");
        
        rm_init_submit_field();
    });


    function rmColumnSelector(checkbox) {
        var checkboxes = document.getElementsByName('check')
        checkboxes.forEach((item) => {
            if (item !== checkbox)
                item.checked = false
        });
    }

    function edit_field_in_page(field_type, field_id, page_no) {
        if (field_type == undefined || field_id == undefined)
            return;
        var curr_form_page = page_no;// = (jQuery("#rm_form_page_tabs").tabs("option", "active")) + 1;
        if(["HTMLP","HTMLH","Divider","Spacing","RichText","Timer","Link","YouTubeV","Iframe","ImageV","PriceV","SubCountV","MapV","Form_Chart","FormData","Feed"].indexOf(field_type)>=0)
            var loc = "?page=rm_field_add_widget&rm_form_id=<?php echo esc_attr($data->form_id); ?>&rm_form_page_no=" + curr_form_page + "&rm_field_type";
        else
            var loc = "?page=rm_field_add&rm_form_id=<?php echo esc_attr($data->form_id); ?>&rm_form_page_no=" + curr_form_page + "&rm_field_type";
        loc += ('=' + field_type);
        loc += "&rm_field_id=" + field_id;
        window.location = loc;
    }
    
    function add_new_field_to_page(field_type) {
        var curr_form_page = get_current_form_page();
        var loc = "?page=rm_field_add&rm_form_id=<?php echo esc_attr($data->form_id); ?>&rm_form_page_no=" + curr_form_page + "&rm_row_id=" + row_id_for_field + "&rm_order_in_row=" + field_order_in_row + "&rm_field_type";
        if (field_type !== undefined)
            loc += ('=' + field_type);
        window.location = loc;
    }
    
    function add_new_widget_to_page(widget_type) {
        var curr_form_page = get_current_form_page();//(jQuery("#rm_form_page_tabs").tabs("option", "active")) + 1;
        var loc = "?page=rm_field_add_widget&rm_form_id=<?php echo esc_attr($data->form_id); ?>&rm_form_page_no=" + curr_form_page + "&rm_row_id=" + row_id_for_field + "&rm_order_in_row=" + field_order_in_row + "&rm_field_type";
        if (widget_type !== undefined)
            loc += ('=' + widget_type);
        window.location = loc;
    }
    
    function rm_init_submit_field() {
        jQuery(".rm_field_btn").on("keydown", function(e){
            if(e.keyCode === 13 || e.keyCode === 27) {
                jQuery(this).blur();
                window.getSelection().removeAllRanges();
            } 
        })

        var last_label;

        jQuery(".rm_field_btn").on("focus", function(e){
                var temp = jQuery(this).text().trim();
                if(temp.length)
                    last_label = temp;
        })

        jQuery(".rm_field_btn").on("blur", function(e){
                var temp = jQuery(this).text().trim();
                if(temp.length <= 0)
                    jQuery(this).text(last_label);
                else
                    rm_update_submit_field();
        })

        jQuery("input[name='rm_field_submit_field_align']").change(function(e){
                var $btn_container = jQuery(".rm-field-submit-field-btn-container");
                $btn_container.removeClass("rm-field-btn-align-left rm-field-btn-align-center rm-field-btn-align-right");
                $btn_container.addClass("rm-field-btn-align-"+jQuery(this).val());
                rm_update_submit_field();
        })

    }

    function rm_update_submit_field(){
        var data = {
            'submit_btn_label': jQuery("#rm_field_sub_button").text().trim(),                                
            'btn_align': jQuery("[name='rm_field_submit_field_align']:checked").val(),
        };

        var data = {
            'action': 'rm_update_submit_field',
            'rm_sec_nonce': '<?php echo wp_create_nonce('rm_ajax_secure'); ?>',
            'data': data,
            'form_id': <?php echo esc_attr($data->form_id); ?>
        };
        jQuery(".rm-field-submit-ajax-loader").css("visibility", "visible");
        jQuery.post(ajaxurl, data, function (response) {
            jQuery(".rm-field-submit-ajax-loader").css("visibility", "hidden");
        });
    }
    
    function get_current_form_page() {
        return curr_form_page_for_field;
    }
</script>

<script>
    jQuery(function($) {
        $( "#rm-field-sortable" ).sortable();
        $( "#rm-field-sortable" ).disableSelection();
        $( "#rm-field-sortable" ).sortable({ axis: 'y' });
    });
    
    jQuery('#rm-row-add-edit-form').submit(function(e) {
        jQuery(this).find('input[type=submit]').prop('disabled', true);
    });
</script>

<style>
   .admin_page_rm_field_manage .rm-formflow-top-bar {
       margin: 100px 0px 10px 5%;
   }
   
   .wp-core-ui.admin_page_rm_field_manage .notice {
       display:none
   }
   
   .wp-core-ui .rmagic::before {
       display: none;
   }
</style>
<?php } ?>
