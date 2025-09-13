<?php
class PuzzlingCRM_Settings_Handler {

    /**
     * The option name in the wp_options table.
     * @var string
     */
    private const OPTION_KEY = 'puzzlingcrm_settings';

    /**
     * Get all settings from the database.
     * @return array
     */
    public static function get_all_settings() {
        return get_option(self::OPTION_KEY, []);
    }

    /**
     * Get a specific setting by key.
     *
     * @param string $key The key of the setting.
     * @param mixed $default The default value if the key is not found.
     * @return mixed
     */
    public static function get_setting($key, $default = '') {
        $settings = self::get_all_settings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Update all settings.
     *
     * @param array $settings An array of settings to save.
     * @return bool
     */
    public static function update_settings($settings) {
        if (!is_array($settings)) {
            return false;
        }
        return update_option(self::OPTION_KEY, $settings);
    }
}