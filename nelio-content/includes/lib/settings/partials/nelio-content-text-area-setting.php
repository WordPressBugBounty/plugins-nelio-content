<?php
/**
 * Displays an text area setting.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings/partials
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * List of vars used in this partial:
 *
 * @var Nelio_Content_Abstract_Setting $this This instance.
 *
 * @var string  $id          The identifier of this field.
 * @var string  $name        The name of this field.
 * @var string  $value       The concrete value of this field (or an empty string).
 * @var boolean $disabled    Whether this checkbox is disabled or not.
 * @var string  $placeholder Optional. A default placeholder.
 * @var string  $desc        Optional. The description of this field.
 * @var string  $more        Optional. A link with more information about this field.
 */

?>

<textarea id="<?php echo esc_attr( $id ); ?>" cols="40" rows="4" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php disabled( $disabled ); ?> name="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $value ); ?></textarea>

<?php
if ( ! empty( $desc ) ) {
	?>
	<div class="setting-help" style="display:none;">
		<p
			<?php
			if ( $disabled ) {
				echo 'style="opacity:0.6"';
			}
			?>
		><span class="description">
			<?php
			$this->print_html( $desc );
			if ( ! empty( $more ) ) {
				?>
				<a href="<?php echo esc_url( $more ); ?>"><?php echo esc_html_x( 'Read more…', 'user', 'nelio-content' ); ?></a>
				<?php
			}
			?>
		</span></p>
	</div>
	<?php
}
?>
