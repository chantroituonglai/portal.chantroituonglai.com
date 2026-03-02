<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-mb-4">Better Menubar</h4>
            <p class="tw-text-neutral-600 tw-mb-6">Tối ưu thanh menu bên trái và khu vực cài đặt để không kéo giãn chiều cao nội dung, cải thiện UX.</p>

            <?php echo form_open(admin_url('better_menubar')); ?>
              <div class="checkbox checkbox-primary">
                <input type="checkbox" id="better_menubar_enabled" name="better_menubar_enabled" <?php echo $enabled ? 'checked' : '';?> />
                <label for="better_menubar_enabled">Kích hoạt Better Menubar</label>
              </div>

              <div class="form-group mtop20">
                <label class="control-label d-block">Chế độ Sidebar</label>
                <div class="radio radio-primary radio-inline">
                  <input type="radio" id="bm_mode_fixed" name="better_menubar_sidebar_mode" value="fixed" <?php echo $sidebar_mode==='fixed'?'checked':'';?>>
                  <label for="bm_mode_fixed">Fixed (khuyến nghị)</label>
                </div>
                <div class="radio radio-primary radio-inline">
                  <input type="radio" id="bm_mode_sticky" name="better_menubar_sidebar_mode" value="sticky" <?php echo $sidebar_mode==='sticky'?'checked':'';?>>
                  <label for="bm_mode_sticky">Sticky</label>
                </div>
              </div>

              <div class="checkbox checkbox-primary mtop10">
                <input type="checkbox" id="better_menubar_header_offset" name="better_menubar_header_offset" <?php echo $header_offset ? 'checked' : '';?> />
                <label for="better_menubar_header_offset">Bù khoảng trống cho header cố định (offset nội dung)</label>
              </div>

              <div class="checkbox checkbox-primary mtop10">
                <input type="checkbox" id="better_menubar_pinned_enabled" name="better_menubar_pinned_enabled" <?php echo $pinned_enabled ? 'checked' : '';?> />
                <label for="better_menubar_pinned_enabled">Bật toggle danh sách dự án ghim (Pinned)</label>
              </div>

              <div class="checkbox checkbox-primary mtop10">
                <input type="checkbox" id="better_menubar_header_fixed" name="better_menubar_header_fixed" <?php echo $header_fixed ? 'checked' : '';?> />
                <label for="better_menubar_header_fixed">Cố định header trên đỉnh màn hình (fixed)</label>
              </div>

              <hr/>
              <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
            <?php echo form_close(); ?>

            <hr class="hr-panel-heading"/>
            <div class="tw-text-sm tw-text-neutral-600">
              Gợi ý: Bạn có thể điều chỉnh thêm CSS nếu muốn tinh chỉnh giao diện. Module này ghi đè logic tính chiều cao cũ và cho phép thanh menu cuộn độc lập theo viewport.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
