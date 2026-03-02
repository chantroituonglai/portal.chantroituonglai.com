<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ---- Helpers to read options safely (object or array or null) ----
 */
function _options_is_obj($opt) {
    return is_object($opt);
}
function _options_get($opt, $slug) {
    if (is_object($opt) && isset($opt->{$slug}) && is_object($opt->{$slug})) {
        return $opt->{$slug};
    }
    return null;
}
function _options_get_child($opt, $parentSlug, $childSlug) {
    $p = _options_get($opt, $parentSlug);
    if ($p && isset($p->children) && is_object($p->children) && isset($p->children->{$childSlug}) && is_object($p->children->{$childSlug})) {
        return $p->children->{$childSlug};
    }
    return null;
}
function _opt_prop($obj, $prop, $default=null) {
    return (is_object($obj) && property_exists($obj, $prop)) ? $obj->{$prop} : $default;
}

/**
 * Return options as stdClass (never null)
 */
function _normalize_menu_options($json)
{
    // get_option có thể trả null/"" -> thành đối tượng rỗng
    $decoded = json_decode($json ?: '{}');
    return is_object($decoded) ? $decoded : (object)[];
}

/**
 * Public APIs Perfex sẽ gọi
 */
function app_admin_sidebar_custom_options($items)
{
    return _apply_menu_items_options($items, _normalize_menu_options(get_option('aside_menu_active')));
}

function app_admin_sidebar_custom_positions($items)
{
    return _apply_menu_items_position($items, _normalize_menu_options(get_option('aside_menu_active')));
}

function app_admin_setup_menu_custom_options($items)
{
    return _apply_menu_items_options($items, _normalize_menu_options(get_option('setup_menu_active')));
}

function app_admin_setup_menu_custom_positions($items)
{
    return _apply_menu_items_position($items, _normalize_menu_options(get_option('setup_menu_active')));
}

/**
 * --- Fix các Warning: stdClass::$icon, "children" undefined ---
 */
function _apply_menu_items_options($items, $options)
{
    foreach ($items as $key => $item) {
        $slug = isset($item['slug']) ? $item['slug'] : null;
        if (!$slug) { continue; }

        $opt = _options_get($options, $slug);

        if ($opt) {
            // Disabled main
            if (_opt_prop($opt, 'disabled') === 'true') {
                unset($items[$key]);
                continue;
            }

            // Custom icon main (chỉ set khi có field icon)
            if (property_exists($opt, 'icon')) {
                if ($opt->icon === false) {
                    $items[$key]['icon'] = '';
                } elseif (!empty($opt->icon)) {
                    $items[$key]['icon'] = $opt->icon;
                }
            }
        }

        // Children có thể không tồn tại -> phải guard
        if (!isset($items[$key]['children']) || !is_array($items[$key]['children'])) {
            continue;
        }

        foreach ($items[$key]['children'] as $childKey => $child) {
            $cSlug = isset($child['slug']) ? $child['slug'] : null;
            if (!$cSlug) { continue; }

            $cOpt = _options_get_child($options, $slug, $cSlug);
            if (!$cOpt) { continue; }

            if (_opt_prop($cOpt, 'disabled') === 'true') {
                unset($items[$key]['children'][$childKey]);
                continue;
            }

            if (property_exists($cOpt, 'icon')) {
                if ($cOpt->icon === false) {
                    $items[$key]['children'][$childKey]['icon'] = '';
                } elseif (!empty($cOpt->icon)) {
                    $items[$key]['children'][$childKey]['icon'] = $cOpt->icon;
                }
            }
        }
    }

    return $items;
}

/**
 * Reorder + apply position (an toàn với children/options rỗng)
 */
function _apply_menu_items_position($items, $options)
{
    // Nếu $options là object (chuẩn), vẫn cần build lại thứ tự theo cấu hình builder (mảng thứ tự lưu trong DB)
    // Một số site lưu "cây" vị trí dưới dạng array JSON -> json_decode thành array -> ở đây hỗ trợ cả 2.
    $CI = &get_instance();

    // Khi $options là object positions: {slug: {position: n, children: {childSlug: {position:n}}}}
    // Còn cấu trúc "builder snapshot" (mảng) – có thể lưu nơi khác; để an toàn, block dưới chỉ chạy nếu $options là array tuần tự
    if (is_array($options)) {
        $newItems          = [];
        $newItemsAddedKeys = [];

        foreach ($options as $key => $optNode) {
            if (!is_object($optNode) || !property_exists($optNode, 'id')) { continue; }
            $newItem = $CI->app_menu->filter_item($items, $optNode->id);
            if ($newItem) {
                $newItems[$key]      = $newItem;
                $newItemsAddedKeys[] = $key;
                $newItems[$key]['children'] = [];

                if (isset($optNode->children) && is_array($optNode->children)) {
                    foreach ($optNode->children as $child) {
                        if (is_object($child) && property_exists($child, 'id')) {
                            $newChildItem = $CI->app_menu->filter_item($items, $child->id);
                            if ($newChildItem) {
                                $newItems[$key]['children'][] = $newChildItem;
                                $newItemsAddedKeys[]          = $newChildItem['slug'];
                            }
                        }
                    }
                }
            }
        }

        // Fill phần còn thiếu
        foreach ($items as $k => $it) {
            if (!in_array($k, $newItemsAddedKeys, true)) {
                $newItems[$k] = $it;
            }
            if (isset($it['collapse']) && isset($it['children']) && is_array($it['children'])) {
                foreach ($it['children'] as $child) {
                    if (isset($child['slug']) && !in_array($child['slug'], $newItemsAddedKeys, true)) {
                        $newItems[$k]['children'][] = $child;
                    }
                }
                if (isset($newItems[$k]['children'])) {
                    $newItems[$k]['children'] = Arr::uniqueByKey($newItems[$k]['children'], 'slug');
                }
            }
        }

        $items = $newItems;
    }

    // Apply position từ $options (object) nếu có
    foreach ($items as $key => $item) {
        $slug = isset($item['slug']) ? $item['slug'] : null;
        if (!$slug) { continue; }

        $opt = _options_get($options, $slug);
        if ($opt && property_exists($opt, 'position')) {
            $items[$key]['position'] = (int) $opt->position;
        }

        if (isset($items[$key]['children']) && is_array($items[$key]['children'])) {
            foreach ($items[$key]['children'] as $childKey => $child) {
                $cSlug = isset($child['slug']) ? $child['slug'] : null;
                if (!$cSlug) { continue; }
                $cOpt = _options_get_child($options, $slug, $cSlug);
                if ($cOpt && property_exists($cOpt, 'position')) {
                    $items[$key]['children'][$childKey]['position'] = (int) $cOpt->position;
                }
            }
        }
    }

    return $items;
}

/**
 * Tìm child option trong snapshot dạng array (giữ lại cho tương thích)
 */
function _menu_options_filter_child($menu_options, $slug)
{
    if (!is_array($menu_options)) {
        return false;
    }
    foreach ($menu_options as $option) {
        if (is_object($option) && isset($option->children) && is_array($option->children)) {
            foreach ($option->children as $child) {
                if (is_object($child) && isset($child->id) && $child->id == $slug) {
                    return $child;
                }
            }
        }
    }
    return false;
}

/**
 * Lấy icon an toàn, không phát sinh Warning khi thiếu thuộc tính icon/children
 */
function app_get_menu_setup_icon($menu_options_raw, $slug, $group)
{
    // Chuẩn hóa: ưu tiên object cho truy cập nhanh
    $menu_options = is_object($menu_options_raw)
        ? $menu_options_raw
        : (is_array($menu_options_raw) ? $menu_options_raw : _normalize_menu_options('{}'));

    $parentOpt = _options_get($menu_options, $slug);
    $childOpt  = _menu_options_filter_child(is_array($menu_options) ? $menu_options : [], $slug); // chỉ dùng cho snapshot array

    // Không có option áp dụng -> icon mặc định
    if (!$parentOpt && $childOpt === false) {
        return get_instance()->app_menu->get_initial_icon($slug, $group);
    }

    // Parent set icon = false -> rỗng
    if ($parentOpt && property_exists($parentOpt, 'icon') && $parentOpt->icon === false) {
        return '';
    }

    // Child set icon = false -> rỗng
    if (is_object($childOpt) && property_exists($childOpt, 'icon') && $childOpt->icon === false) {
        return '';
    }

    // Parent: icon = '' -> trả icon mặc định
    if ($parentOpt && property_exists($parentOpt, 'icon') && $parentOpt->icon === '') {
        return get_instance()->app_menu->get_initial_icon($slug, $group);
    } elseif ($parentOpt && property_exists($parentOpt, 'icon') && $parentOpt->icon) {
        return $parentOpt->icon;
    }

    // Child: icon = '' -> trả icon mặc định
    if (is_object($childOpt) && property_exists($childOpt, 'icon') && $childOpt->icon === '') {
        return get_instance()->app_menu->get_initial_icon($slug, $group);
    } elseif (is_object($childOpt) && property_exists($childOpt, 'icon') && $childOpt->icon) {
        return $childOpt->icon;
    }

    // fallback
    return get_instance()->app_menu->get_initial_icon($slug, $group);
}
