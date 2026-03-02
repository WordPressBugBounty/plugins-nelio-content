<?php
/**
 * Displays a select setting.
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
 * @var string  $id       The identifier of this field.
 * @var string  $name     The name of this field.
 * @var list<array{value:string, label:string, desc?: string, disabled?:bool}> $options The list of options.
 * @var string  $value    The concrete value of this field (or an empty string).
 * @var string  $desc     Optional. The description of this field.
 * @var string  $more     Optional. A link with more information about this field.
 */

?>

<div id="<?php echo esc_attr( "{$id}-wrapper" ); ?>">
	<select
		id="<?php echo esc_attr( $id ); ?>"
		name="<?php echo esc_attr( $name ); ?>"
	>

		<?php
		foreach ( $options as $nelio_content_option ) {
			?>
			<option value="<?php echo esc_attr( $nelio_content_option['value'] ); ?>"
				<?php
				if ( $nelio_content_option['value'] === $value ) {
					echo ' selected="selected"';
				}
				?>
				<?php
				if ( ! empty( $nelio_content_option['disabled'] ) ) {
					echo ' disabled';
				}
				?>
			>
			<?php $this->print_html( $nelio_content_option['label'] ); ?>
		</option>
			<?php
		}
		?>

	</select>
</div>

<script>
(function() {
	try {
		const wrapper = wp.element.createRoot( document.getElementById( <?php echo wp_json_encode( "{$id}-wrapper" ); ?> ) );
		wrapper.render(
			wp.element.createElement(
				wp.components.SelectControl,
				<?php
					echo wp_json_encode(
						array(
							'id'                      => $id,
							'name'                    => $name,
							'options'                 => array_map(
								fn( $option ) => array(
									'label'    => wp_kses( $option['label'], array() ),
									'value'    => $option['value'],
									'disabled' => ! empty( $option['disabled'] ) ? true : null,
								),
								$options
							),
							'defaultValue'            => $value,
							'__next40pxDefaultSize'   => true,
							'__nextHasNoMarginBottom' => true,
						)
					);
					?>
			)
		);
	} catch( e ) { console.error( 'Error rendering Nelio Content select setting', e ); }
})();
</script>

<?php
$nelio_content_described_options = array();
foreach ( $options as $nelio_content_option ) {
	if ( isset( $nelio_content_option['desc'] ) ) {
		array_push( $nelio_content_described_options, $nelio_content_option );
	}
}

if ( ! empty( $desc ) ) {
	?>
	<div class="setting-help" style="display:none;">
	<p
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

	<?php
	if ( count( $nelio_content_described_options ) > 0 ) {
		?>
		<ul
			style="list-style-type:disc;margin-left:3em;"
		>
			<?php
			foreach ( $nelio_content_described_options as $nelio_content_option ) {
				?>
				<li><span class="description">
					<strong><?php $this->print_html( $nelio_content_option['label'] ); ?>.</strong>
					<?php $this->print_html( $nelio_content_option['desc'] ); ?>
				</span></li>
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
