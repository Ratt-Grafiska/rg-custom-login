<?php
if (!class_exists("RgGitUpdater")) {
  class RgGitUpdater
  {
    private static $instance = null;
    private $github_api_url = "https://api.github.com/repos/";

    private function __construct()
    {
      add_filter("pre_set_site_transient_update_plugins", [
        $this,
        "check_for_update",
      ]);
      add_filter("plugins_api", [$this, "plugin_info"], 10, 3);
    }

    public static function get_instance()
    {
      if (self::$instance === null) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    public function check_for_update($transient)
    {
      if (!is_object($transient)) {
        $transient = new stdClass();
      }
      if (!isset($transient->checked)) {
        return $transient;
      }

      // Hämta alla installerade plugins
      $plugins = get_plugins();

      foreach ($plugins as $plugin_path => $plugin_info) {
        if (!isset($plugin_info["UpdateURI"])) {
          continue; // Hoppa över plugins som saknar UpdateURI
        }

        $github_repo = trim(
          parse_url($plugin_info["UpdateURI"], PHP_URL_PATH),
          "/"
        );

        if (!$github_repo) {
          continue; // Om UpdateURI är felaktigt, hoppa över
        }

        $github_url = $this->github_api_url . $github_repo . "/releases/latest";
        $response = wp_remote_get($github_url, [
          "headers" => [
            "Accept" => "application/vnd.github.v3+json",
            "User-Agent" =>
              "WordPress/" . get_bloginfo("version") . "; " . home_url(),
          ],
        ]);

        if (is_wp_error($response)) {
          continue;
        }

        $release = json_decode(wp_remote_retrieve_body($response));

        if (!isset($release->tag_name)) {
          continue;
        }

        $new_version = $release->tag_name;
        $current_version = $plugin_info["Version"];

        if (version_compare($current_version, $new_version, "<")) {
          $transient->response[$plugin_path] = (object) [
            "slug" => plugin_basename($plugin_path),
            "new_version" => $new_version,
            "package" => $release->zipball_url,
            "url" => $release->html_url,
          ];
        }
      }

      return $transient;
    }

    public function plugin_info($result, $action, $args)
    {
      if ($action !== "plugin_information") {
        return $result;
      }

      $plugins = get_plugins();
      foreach ($plugins as $plugin_path => $plugin_info) {
        if (plugin_basename($plugin_path) !== $args->slug) {
          continue;
        }

        if (!isset($plugin_info["UpdateURI"])) {
          return $result;
        }

        $github_repo = trim(
          parse_url($plugin_info["UpdateURI"], PHP_URL_PATH),
          "/"
        );
        if (!$github_repo) {
          return $result;
        }

        $github_url = $this->github_api_url . $github_repo . "/releases/latest";
        $response = wp_remote_get($github_url, [
          "headers" => ["Accept" => "application/vnd.github.v3+json"],
        ]);

        if (is_wp_error($response)) {
          return $result;
        }

        $release = json_decode(wp_remote_retrieve_body($response));

        if (!isset($release->tag_name)) {
          return $result;
        }

        return (object) [
          "name" => $plugin_info["Name"],
          "slug" => plugin_basename($plugin_path),
          "version" => $release->tag_name,
          "author" => $plugin_info["Author"],
          "homepage" => $release->html_url,
          "sections" => [
            "description" => $plugin_info["Description"],
            "changelog" => isset($release->body) ? nl2br($release->body) : "",
          ],
          "download_link" => $release->zipball_url,
        ];
      }

      return $result;
    }
  }

  RgGitUpdater::get_instance();
}
