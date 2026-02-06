<?php
/**
 * Prints the list of tabs and highlights the first one.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings/partials
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * List of required vars:
 *
 * @var Nelio_Content_Abstract_Setting $this This instance.
 *
 * @var string               $current_subpage current subpage.
 * @var string               $current_tab     current tab.
 * @var list<TSettings_Tab>  $tabs            list of tabs.
 */

printf(
	'<input id="nelio-settings-current-subpage" type="hidden" value="%s" />',
	esc_attr( $current_subpage )
);
?>


<h2 class="nav-tab-wrapper">

<?php
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
foreach ( $tabs as $_tab ) {
	printf(
		'<a id="nelio-settings-%1$s" class="%4$s" href="%2$s">%3$s</a>',
		esc_attr( $_tab['name'] ),
		esc_url( $_tab['link'] ?? '' ),
		esc_html( $_tab['label'] ),
		esc_attr( $current_tab === $_tab['name'] ? 'nav-tab nav-tab-active' : 'nav-tab' )
	);
	echo "\n";
}
?>

</h2>

<?php
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
foreach ( $tabs as $_tab ) {
	if ( count( $_tab['pages'] ) <= 1 ) {
		continue;
	}

	if ( $_tab['name'] !== $current_tab ) {
		continue;
	}

	echo '<div style="padding:1em 0">';
	echo implode(
		' | ',
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array_map(
			fn( $p ) => sprintf(
				$current_subpage === $p['name'] ? '<strong>%3$s</strong>' : '<a id="%1$s" href="%2$s">%3$s</a>',
				esc_attr( "nelio-settings__{$p['name']}__subpage-toggler" ),
				esc_attr( $p['link'] ?? '' ),
				esc_html( $p['label'] ),
			),
			$_tab['pages']
		)
	);

	echo '</div>';
}
?>
