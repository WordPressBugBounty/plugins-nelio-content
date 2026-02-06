<?php
/**
 * Displays a radio setting.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings/partials
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * List of vars used in this partial:
 *
 * @var Nelio_Content_Abstract_Setting $this This instance.
 *
 * @var list<array{value:string,label:string,desc?:string}> $options The list of options.
 *                       Each of them is an array with its label, description, and so on.
 * @var string  $name    The name of this field.
 * @var string  $value   The concrete value of this field (or an empty string).
 * @var string  $desc    Optional. The description of this field.
 * @var string  $more    Optional. A link with more information about this field.
 */

?>

<?php /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound */ ?>
<?php foreach ( $options as $option ) { ?>
	<p><input type="radio"
		name="<?php echo esc_attr( $name ); ?>"
		value="<?php echo esc_attr( $option['value'] ); ?>"
		<?php checked( $option['value'] === $value ); ?> />
		<?php $this->print_html( $option['label'] ); ?></p>
	<?php
}
?>

<?php
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$described_options = array();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
foreach ( $options as $option ) {
	if ( isset( $option['desc'] ) ) {
		array_push( $described_options, $option );
	}
}

if ( ! empty( $desc ) ) {
	?>
	<div class="setting-help" style="display:none;">
		<p class="description">
		<?php
		$this->print_html( $desc );
		if ( ! empty( $more ) ) {
			?>
			<a href="<?php echo esc_url( $more ); ?>"><?php echo esc_html_x( 'Read more&hellip;', 'user', 'nelio-content' ); ?></a>
			<?php
		}
		?>
		</p>

		<?php if ( count( $described_options ) > 0 ) { ?>
			<ul style="list-style-type:disc;margin-left:3em;">
				<?php /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound */ ?>
				<?php foreach ( $described_options as $option ) { ?>
					<li><p style="display:inline" class="description"><strong>
						<?php $this->print_html( $option['label'] ); ?>.</strong>
						<?php $this->print_html( $option['desc'] ); ?></p></li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>

	</div>
	<?php
}
?>
