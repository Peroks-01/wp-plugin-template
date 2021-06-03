<?php namespace peroks\plugin_customer\plugin_package;
/**
 * Displays input fields and forms.
 *
 * @author Per Egil Roksvaag
 */
class Form
{
	const FILTER_FORM         = Main::PREFIX . '_form';
	const FILTER_FORM_FIELD   = Main::PREFIX . '_form_field';
	const FILTER_FORM_LABEL   = Main::PREFIX . '_form_label';
	const FILTER_FORM_CONTROL = Main::PREFIX . '_form_control';

	/* -------------------------------------------------------------------------
	 * Input fields
	 * ---------------------------------------------------------------------- */

	/**
	 * Displays a text input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function text( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a text search field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function search( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays an tel input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function tel( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays an email input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function email( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays an url input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function url( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a password input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function password( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a number input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function number( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a range input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function range( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a date input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function date( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a time input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function time( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a week input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function week( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a month input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function month( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a color input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function color( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a file input field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function file( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays an checkbox field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function checkbox( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a submit button.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function submit( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays a reset button.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function reset( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Displays an image button field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function image( array $attr = array() ) {
		return $this->input( $attr, __FUNCTION__ );
	}

	/**
	 * Outputs a hidden form field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function hidden( $attr = array() ) {
		return vsprintf( '<input type="hidden" name="%s" value="%s" />', array(
			esc_attr( $attr['name'] ?? '' ),
			esc_attr( $attr['value'] ?? '' ),
		) );
	}

	/**
	 * Displays a form field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function input( $attr, $type = 'text' ) {
		$attr = wp_parse_args( $attr, array(
			'type' => $type,
		) );

		$html = vsprintf( '<input class="peroks-form-control"%s />', array(
			$this->clean_attibutes( $attr, $attr['type'] ),
		) );

		$html = apply_filters( self::FILTER_FORM_CONTROL, $html, $attr );
		return $this->field( $attr, $this->label( $attr, $html ) );
	}

	/**
	 * Displays a text area.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function textarea( array $attr = array() ) {
		$attr = wp_parse_args( $attr, array(
			'cols'    => 20,
			'rows'    => 5,
			'stretch' => true,
		) );

		$attr = array_merge( $attr, array(
			'type' => 'textarea',
		) );

		$output = vsprintf( '<textarea class="peroks-form-control"%s>%s</textarea>', array(
			$this->clean_attibutes( $attr, $attr['type'] ),
			trim( esc_html( $attr['value'] ?? '' ) ),
		) );

		$output = $this->label( $attr, $output );
		return $this->field( $attr, $output );
	}

	/**
	 * Displays a select form field.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function select( $attr ) {
		$attr = wp_parse_args( $attr, array(
			'type'        => 'select',
			'placeholder' => null,
			'options'     => array(),
			'value'       => '',
		) );

		$placeholder = $attr['placeholder'];
		$value       = $attr['value'];
		$options     = array();

		if ( isset( $placeholder ) ) {
			$options[] = sprintf( '<option value="">%s</option>', esc_html( $placeholder ) );
		}

		foreach ( $attr['options'] as $key => $option ) {
			$key       = is_string( $key ) ? $key : $option;
			$options[] = vsprintf( '<option value="%s" %s>%s</option>', array(
				esc_attr( $key ),
				selected( $key, $value, false ),
				esc_html( $option ),
			) );
		}

		$html = vsprintf( '<select class="peroks-form-control"%s>%s</select>', array(
			$this->clean_attibutes( $attr, $attr['type'] ),
			join( "\n", $options ),
		) );

		$html = apply_filters( self::FILTER_FORM_CONTROL, $html, $attr );
		return $this->field( $attr, $this->label( $attr, $html ) );
	}

	/**
	 * Displays a button.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html
	 */
	public function button( array $attr = array() ) {
		$attr = wp_parse_args( $attr, array(
			'type'  => 'button',
			'value' => __( 'Submit', '[plugin-text-domain]' ),
		) );

		$output[] = vsprintf( '<button class="peroks-form-control"%s>%s</button>', array(
			$this->clean_attibutes( $attr, $attr['type'] ),
			trim( $attr['value'] ),
		) );

		return $this->field( $attr, join( ' ', $output ) );
	}

	/* -------------------------------------------------------------------------
	 * Wrapper
	 * ---------------------------------------------------------------------- */

	/**
	 * Wraps a field in a label tag.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @param string $content The input field to wrap.
	 * @param string $pos Label position: "left" or "right" of the input field.
	 * @return string Html
	 */
	public function label( array $attr, string $content, string $pos = 'left' ) {
		$attr = wp_parse_args( $attr, array(
			'label'     => '',
			'label-pos' => $pos,
		) );

		$output[] = $content;

		if ( $label = trim( $attr['label'] ) ) {
			$output[] = sprintf( '<span class="peroks-form-label %s">%s</span>', $pos, $label );
		}
		if ( 'left' == $attr['label-pos'] ) {
			$output = array_reverse( $output );
		}

		$html = sprintf( '<label>%s</label>', join( '', $output ) );
		return apply_filters( self::FILTER_FORM_LABEL, $html, $attr, $content, $pos );
	}

	/**
	 * Container for labels, controls and descriptions.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @param string $content The input field to wrap.
	 * @param string $pos The description position: "top" or "bottom".
	 * @return string Html
	 */
	public function field( array $attr, string $content, string $pos = 'bottom' ) {
		$attr = wp_parse_args( $attr, array(
			'name'            => '',
			'type'            => 'text',
			'class'           => array(),
			'stretch'         => false,
			'required'        => false,
			'description'     => '',
			'description-pos' => $pos,
		) );

		$output[] = $content;

		if ( $desc = trim( $attr['description'] ) ) {
			$output[] = sprintf( '<p class="peroks-form-description %s">%s</p>', $pos, $desc );
		}
		if ( 'top' == $attr['description-pos'] ) {
			$output = array_reverse( $output );
		}

		$class = Utils::instance()->parse_class( $attr['class'] );
		$class = array_merge( $class, array_filter( array(
			'peroks-form-field',
			$attr['stretch'] ? 'stretch' : null,
			$attr['required'] ? 'required' : null,
		) ) );

		$html = vsprintf( '<div class="%s" data-type="%s" data-name="%s">%s</div>', array(
			join( ' ', $class ),
			esc_attr( $attr['type'] ),
			esc_attr( $attr['name'] ),
			join( '', $output ),
		) );

		return apply_filters( self::FILTER_FORM_FIELD, $html, $attr, $content, $pos );
	}

	/**
	 * Container for labels, fields and descriptions.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @param string $content The input field to wrap.
	 * @return string Html
	 */
	public function form( array $attr, string $content ) {
		$class   = Utils::instance()->parse_class( $attr['class'] ?? array() );
		$class[] = 'peroks-form';

		$html = vsprintf( '<form class="%s"%s>%s</form>', array(
			join( ' ', $class ),
			$this->clean_attibutes( $attr, 'form' ),
			$content,
		) );

		return apply_filters( self::FILTER_FORM, $html, $attr );
	}

	/* -------------------------------------------------------------------------
	 * Internal utils
	 * ---------------------------------------------------------------------- */

	/**
	 * Cleans and transforms an associative array of key/value pairs to html attributes.
	 *
	 * @param array $attr HTML attributes as key/value pairs.
	 * @return string Html attributes
	 */
	public function clean_attibutes( $attr, $type ) {
		$clean = array_intersect_key( $attr, $this->whitelist( $type ) );
		$call  = function ( $key, $value ) {
			if ( $value && is_bool( $value ) ) {
				return sanitize_key( $key );
			}
			return sanitize_key( $key ) . '="' . esc_attr( $value ) . '"';
		};

		if ( $clean ) {
			return ' ' . join( ' ', array_map( $call, array_keys( $clean ), $clean ) );
		}
	}

	/**
	 * Gets an array of allowed attributes for the given input field.
	 *
	 * @param string $type Input type: 'text', 'number', 'file', 'checkbox', 'radio', 'image', 'textarea', 'submit' or 'button'.
	 * @return array An array with the allowed attributes as keys.
	 */
	protected static function whitelist( string $type ) {
		$global = array(
			'accesskey'       => 'Provides a hint for generating a keyboard shortcut for the current element',
			'autocapitalize'  => 'Controls whether and how text input is automatically capitalized',
			'class'           => 'A space-separated list of the classes of the element',
			'contenteditable' => 'An enumerated attribute indicating if the element should be editable by the user',
			'dir'             => 'An enumerated attribute indicating the directionality of the element\'s text',
			'draggable'       => 'An enumerated attribute indicating whether the element can be dragged',
			'enterkeyhint'    => 'Hints what action label (or icon) to present for the enter key on virtual keyboards',
			'hidden'          => 'A Boolean attribute indicates that the element is not yet, or is no longer, relevant',
			'id'              => 'Defines a unique identifier (ID) which must be unique in the whole document',
			'inputmode'       => 'Provides a hint to browsers as to the type of virtual keyboard configuration to use',
			'is'              => 'Allows you to specify that a standard HTML element should behave like a registered custom built-in element',
			'itemid'          => 'The unique, global identifier of an item',
			'itemprop'        => 'Used to add properties to an item',
			'itemref'         => 'Properties can be associated with the item using an itemref',
			'itemscope'       => 'Specify that the HTML contained in a block is about a particular item',
			'itemtype'        => 'Specifies the URL of the vocabulary',
			'lang'            => 'Helps define the language of an element',
			'nonce'           => 'A cryptographic nonce',
			'part'            => 'A space-separated list of the part names of the element',
			'slot'            => 'Assigns a slot in a shadow DOM shadow tree to an element',
			'spellcheck'      => 'An enumerated attribute defines whether the element may be checked for spelling errors',
			'style'           => 'Contains CSS styling declarations to be applied to the element',
			'tabindex'        => 'An integer attribute indicating if the element can take input focus',
			'title'           => 'Contains a text representing advisory information related to the element it belongs to',
			'translate'       => 'An enumerated attribute',
		);

		$all = array(
			'autocomplete' => 'Hint for form autofill feature',
			'autofocus'    => 'Automatically focus the form control when the page is loaded',
			'disabled'     => 'Whether the form control is disabled',
			'form'         => 'Associates the control with a form element',
			'name'         => 'Name of the form control. Submitted with the form as part of a name/value pair',
			'readonly'     => 'Boolean. The value is not editable',
			'required'     => 'Boolean. A value is required or must be check for the form to be submittable',
		);

		$input = array(
			'list'  => 'Value of the id attribute of the <datalist> of autocomplete options',
			'type'  => 'Type of form control',
			'value' => 'Current value of the form control. Submitted with the form as part of a name/value pair',
		);

		$text = array(
			'dirname'     => 'Name of form field to use for sending the element\'s directionality in form submission',
			'maxlength'   => 'Maximum length (number of characters) of value',
			'minlength'   => 'Minimum length (number of characters) of value',
			'pattern'     => 'Pattern the value must match to be valid',
			'placeholder' => 'Text that appears in the form control when it has no value set',
			'size'        => 'Size of the control',
		);

		$number = array(
			'max'  => 'Maximum value',
			'min'  => 'Minimum value',
			'step' => 'Incremental values that are valid',
		);

		$file = array(
			'accept'  => 'Hint for expected file type in file upload controls',
			'capture' => 'Media capture input method in file upload controls',
		);

		$check = array(
			'checked' => 'Whether the command or control is checked',
		);

		$submit = array(
			'type'           => 'Button type',
			'formaction'     => 'URL to use for form submission',
			'formenctype'    => 'Form data set encoding type to use for form submission',
			'formmethod'     => 'HTTP method to use for form submission',
			'formnovalidate' => 'Bypass form control validation for form submission',
			'formtarget'     => 'Browsing context for form submission',
		);

		$image = array(
			'alt'    => 'Alt attribute for the image type. Required for accessibility',
			'height' => 'Same as height attribute for <img> vertical dimension',
			'src'    => 'Same as src attribute for <img> address of image resource',
			'width'  => 'Same as width attribute for <img>',
		);

		$textarea = array(
			'cols'       => 'The visible width of the text control, in average character widths',
			'rows'       => 'The number of visible text lines for the control',
			'spellcheck' => 'Specifies whether the <textarea> is subject to spell checking by the underlying browser/OS',
			'wrap'       => 'Indicates how the control wraps text',
		);

		$form = array(
			'accept-charset' => 'Space-separated character encodings the server accepts.',
			'autocomplete'   => 'Hint for form autofill feature',
			'name'           => 'Name of the form control. Submitted with the form as part of a name/value pair',
			'rel'            => 'Creates a hyperlink or annotation depending on the value',
			'action'         => 'The URL that processes the form submission',
			'enctype'        => 'The MIME type of the form submission',
			'method'         => 'The HTTP method to submit the form with.',
			'novalidate'     => 'This Boolean attribute indicates that the form shouldn\'t be validated when submitted',
			'target'         => 'Indicates where to display the response after submitting the form.',
		);

		switch ( $type ) {
			case 'text':
			case 'email':
			case 'date':
			case 'password':
				return array_merge( $global, $all, $input, $text );
			case 'number':
				return array_merge( $global, $all, $input, $text, $number );
			case 'file':
				return array_merge( $global, $all, $input, $file );
			case 'checkbox':
			case 'radio':
				return array_merge( $global, $all, $input, $check );
			case 'image':
				return array_merge( $global, $all, $submit, $image );
			case 'textarea':
				return array_merge( $global, $all, $text, $textarea );
			case 'select':
				return array_merge( $global, $all );
			case 'submit':
			case 'button':
				return array_merge( $global, $all, $submit );
			case 'form':
				return array_merge( $global, $form );
			default:
				return array();
		}
	}
}