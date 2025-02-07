<?php

// Funktion för att anpassa inloggningslogo och tema-färger
function custom_login_logo_and_theme_colors()
{
  // Hämta URL till favicon
  $favicon_url = get_site_icon_url();
  $background_color = "#efefef";
  // Hämta färger från theme.json
  $theme_json = wp_get_global_settings();
  $first_accent_color = get_first_accent_color_from_theme_json();
  $background_color = $theme_json["color"]["background"] ?? "#efefef";
  $text_color = $theme_json["color"]["text"] ?? "#333333";
  $link_color = $theme_json["color"]["link"] ?? $first_accent_color;
  $button_background_color =
    $theme_json["color"]["button"]["background"] ?? $first_accent_color;
  $button_text_color = $theme_json["color"]["button"]["text"] ?? "#ffffff";

  echo '<style type="text/css">
		body.login {
			background-color: ' .
    esc_attr($background_color) .
    ';
		}
		body.login #login h1 a {
			background-image: url(' .
    esc_url($favicon_url) .
    ') !important;
			width: 100px; /* Justera bredden efter behov */
			height: 100px; /* Justera höjden efter behov */
			background-size: contain;
		}
		body.login #loginform {
			background-color: ' .
    esc_attr($background_color) .
    ';
			border: none;
			box-shadow: none;
		}
		body.login #loginform p {
			color: ' .
    esc_attr($text_color) .
    ';
		}
		body.login #loginform a {
			color: ' .
    esc_attr($link_color) .
    ';
		}
		body.login #loginform .button-primary {
			background-color: ' .
    esc_attr($button_background_color) .
    ';
			border-color: ' .
    esc_attr($button_background_color) .
    ';
			color: ' .
    esc_attr($button_text_color) .
    ';
		}
		body.login #loginform .button-primary:hover {
			background-color: darken(' .
    esc_attr($button_background_color) .
    ', 10%);
		}
	</style>';
}
add_action("login_enqueue_scripts", "custom_login_logo_and_theme_colors");
function add_logo_to_general_settings()
{
  // Lägg till fältet för webbplatslogotyp
  add_settings_field(
    "site_logo", // ID för fältet
    "Webbplatslogotyp", // Titel på fältet
    "site_logo_callback", // Callback-funktion för att visa fältet
    "general", // Sidan där fältet läggs till
    "default", // Sektionen där fältet placeras
    ["before" => "site_icon"] // Vi använder prioritering via en custom-funktion nedan
  );

  register_setting("general", "site_logo", [
    "type" => "string",
    "sanitize_callback" => "sanitize_text_field",
    "default" => "",
  ]);
}
add_action("admin_init", "add_logo_to_general_settings", 9); // Lägg till med lägre prioritet än standard

function site_logo_callback()
{
  $logo_id = get_theme_mod("custom_logo"); // Hämta logotyp-ID
  $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : "";
  echo '<div style="margin-bottom: 1rem;">';
  if ($logo_url) {
    echo '<img src="' .
      esc_url($logo_url) .
      '" alt="Webbplatslogotyp" style="max-height: 100px; display: block; margin-bottom: 10px;">';
  } else {
    echo "<p>Ingen logotyp vald.</p>";
  }
  echo "</div>";
  echo '<input type="hidden" id="site_logo_id" name="site_logo" value="' .
    esc_attr($logo_id) .
    '">';
  echo '<button class="button button-secondary" id="upload_logo_button">Ladda upp logotyp</button>';
  echo '<button class="button button-link-delete" id="remove_logo_button" style="margin-left: 10px;">Ta bort logotyp</button>';
  echo '<p class="description">Välj en logotyp som visas på din webbplats.</p>';?>
	<script>
		jQuery(document).ready(function($) {
			var mediaUploader;
			$('#upload_logo_button').click(function(e) {
				e.preventDefault();
				if (mediaUploader) {
					mediaUploader.open();
					return;
				}
				mediaUploader = wp.media({
					title: 'Välj en logotyp',
					button: { text: 'Använd denna logotyp' },
					multiple: false
				});
				mediaUploader.on('select', function() {
					var attachment = mediaUploader.state().get('selection').first().toJSON();
					$('#site_logo_id').val(attachment.id);
					location.reload(); // Uppdatera sidan för att visa den nya logotypen
				});
				mediaUploader.open();
			});
			$('#remove_logo_button').click(function(e) {
				e.preventDefault();
				$('#site_logo_id').val('');
				location.reload(); // Uppdatera sidan för att ta bort logotypen
			});
		});
	</script>
	<?php
}

function save_custom_logo_option($old_value, $value, $option)
{
  if ($option === "site_logo") {
    set_theme_mod("custom_logo", $value ? intval($value) : null);
  }
}
add_action("updated_option", "save_custom_logo_option", 10, 3);

function get_first_accent_color_from_theme_json()
{
  // Hämta globala inställningar från theme.json
  $global_settings = wp_get_global_settings();

  // Kontrollera om färgpaletten är definierad
  if (
    isset($global_settings["color"]["palette"]) &&
    is_array($global_settings["color"]["palette"])
  ) {
    foreach ($global_settings["color"]["palette"] as $color) {
      // Kontrollera om det är en accentfärg
      if (isset($color["slug"]) && strpos($color["slug"], "accent") !== false) {
        return $color["color"]; // Returnera färgvärdet
      }
    }
  }

  // Om ingen accentfärg hittades, returnera ett standardvärde eller null
  return null;
}

/* Använd funktionen
$first_accent_color = get_first_accent_color_from_theme_json();
if ($first_accent_color) {
	echo 'Den första accentfärgen är: ' . $first_accent_color;
} else {
	echo 'Ingen accentfärg hittades i theme.json.';
}*/
?>

