// Helper functions

function remove( el ) {
	if ( el ) {
		el = el.parentNode.removeChild( el );
		return el;
	}
}

var postlinkForm = (function( $ ) {
	var linkTypes = {},
		typeDiv  = document.getElementById( 'postlink-types' ),
		deleteDiv  = document.getElementById( 'delete-links' ),
		addTypeInput = document.getElementById( 'link-type-input' ),
		addTypeIdInput = document.getElementById( 'link-type-id-input' ),
		addRevTypeInput = document.getElementById( 'reverse-link-type-input' ),
		addRevTypeIdInput = document.getElementById( 'reverse-link-type-id-input' ),
		addTypeButton = document.getElementById( 'link-type-button' ),
		errorSpan = document.getElementById( 'postlink-errors' ),
		nonce = document.getElementById( 'postlink_update_nonce' ).value;

	function init() {
		// Look for any boxes created in php and sut them up.
		var existingTypes = document.getElementsByClassName( 'postlink-type' );
		for ( var i = 0, len = existingTypes.length; i < len; i++ ) {
			addExisting( existingTypes[i] );
		}

		bindEvents();
	}

	function bindEvents() {
		addTypeButton.onclick = addType;

		// Listen for connection types being deleted.
		events.subscribe( 'removeType', function( id ) {
			delete( linkTypes[id] );
		});

		// Set up link type autocomplete
		$( '#link-type-input, #reverse-link-type-input' ).autocomplete({
			select: function( event, ui ) {
				event.preventDefault();
				event.target.value = ui.item.label;
				$( event.target ).next( 'input' ).val( ui.item.value );
			},
			focus: function( event, ui ) {
				event.preventDefault();
				event.target.value = ui.item.label;
				$( event.target ).next( 'input' ).val( ui.item.value );
			},
			minLength: 2,
			source: function( request, response ) {
				$.ajax({
					url: scriptVars.ajaxUrl,
					dataType: 'json',
					data: {
						text: request.term,
						action: 'postlink_findlink',
						nonce: nonce,
					},
					success: function( data ) {
						response( data );
					}
				});
			},
		});
	}

	function addExisting( container ) {
		var typeId = container.getAttribute( 'data-type-id' ),
			typeName = container.getAttribute( 'data-type-name' ),
			revTypeId = container.getAttribute( 'data-rev-type-id' ),
			revTypeName = container.getAttribute( 'data-rev-type-name' ),
			combinedId = typeId + '/' + revTypeId;

		linkTypes[combinedId] = new PostLinkType( typeId, typeName, revTypeId, revTypeName );
	}

	function addType() {
		var typeId = addTypeIdInput.value || 0,
			typeName = addTypeInput.value,
			revTypeId = addRevTypeIdInput.value || 0,
			revTypeName = addRevTypeInput.value,
			combinedId = typeId + '/' + revTypeId;

		if ( 0 == typeId ) {
			error( 'Please choose a connection type' );
		} else if ( ! ( combinedId in linkTypes ) ) {
			linkTypes[combinedId] = new PostLinkType( typeId, typeName, revTypeId, revTypeName );
		} else {
			linkTypes[combinedId].alert();
			error( 'Already in use' );
		}

		// Empty the form fields.
		addTypeIdInput.value = '';
		addTypeInput.value = '';
		addRevTypeIdInput.value = '';
		addRevTypeInput.value = '';
	}

	function error( message ) {
		errorSpan.innerHTML = message;
		setTimeout( function() {
			errorSpan.innerHTML = '';
		}, 700 );
	}

	return {
		init: init,
		typeDiv: typeDiv,
		deleteDiv: deleteDiv
	}
})( jQuery );

function PostLinkType( typeId, typeName, revTypeId, revTypeName ) {
	this.body;
	this.rowContainer = 0 || 0;
	this.typeId = typeId || 0;
	this.typeName = typeName || '';
	this.revTypeId = revTypeId || 0;
	this.revTypeName = revTypeName || '';
	this.rowCount = 0;

	this.create();
	this.bindEvents();
}


PostLinkType.prototype = {
	bindEvents: function() {
		nonce = document.getElementById( 'postlink_update_nonce' ).value;

		this.body.onclick = function( e ) {
			// Use event delagation
			e = e || event
  			var target = e.target || e.srcElement
			var action = target.getAttribute('data-action')

			switch ( action ) {
				case 'add-link':
					this.addLink();
					break;
				case 'delete-link':
					e.preventDefault();
					this.deleteLink( target.parentNode.parentNode );
					break;
				case 'delete-type':
					e.preventDefault();
					this.destroy( target );
					break;
				default:
					return;
			}
		}.bind( this );

		// Post fields
		jQuery( '.link-name' ).autocomplete({
			create: function() {
				jQuery(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					return jQuery( "<li>" )
						.append( item.label )
						.append( item.append )
						.appendTo( ul );
				};
			},
			select: function( event, ui ) {
				event.preventDefault();
				event.target.value = ui.item.label;
				event.target.parentNode.getElementsByClassName( 'link-id' )[0].value = ui.item.value;
			},
			focus: function( event, ui ) {
				event.preventDefault();
				event.target.value = ui.item.label;
				event.target.parentNode.getElementsByClassName( 'link-id' )[0].value = ui.item.value;
			},
			minLength: 2,
			source: function( request, response ) {
				jQuery.ajax({
					url: scriptVars.ajaxUrl,
					dataType: 'json',
					data: {
						text: request.term,
						action: 'postlink_findpost',
						nonce: nonce,
					},
					success: function( data ) {
						response( data );
					}
				});
			},
		});
	},

	addLink: function() {
		if ( '' !== this.linkIdInput.value ) {
			var insert = rowTemplate.insert( this.linkNameInput.value, this.linkIdInput.value );
			insert.getElementsByClassName( 'link-id-input' )[0].setAttribute( 'name', 'postlinks[' + this.typeId + '][' + this.revTypeId + '][' + this.rowCount + ']' );
			this.rowContainer.appendChild( insert );
		}

		// Empty inputs for next use.
		this.linkNameInput.value = '';
		this.linkIdInput.value = '';
		this.rowCount = this.rowCount + 1;
	},

	deleteLink: function( entry ) {
		var removed,
			postIdInput,
			postId,
			inputName;

		removed = remove( entry ),
		postIdInput = removed.getElementsByClassName( 'link-id-input' )[0],
		postId = postIdInput.getAttribute( 'value' ),
		inputName = 'delete-links[' + this.typeId + '][' + postId + ']';

		// Move the ID input to the delete div.
		postIdInput.setAttribute( 'name', inputName );
		postlinkForm.deleteDiv.appendChild( postIdInput );
	},

	create: function() {
		// Check if the box already exists
		this.body = document.querySelector( '[data-combined="' + this.typeId + '/' + this.revTypeId + '"]' )

		if ( null === this.body ) {
			// The HTML doesn't already exist, so lets creatwe it now.
			this.body = typeTemplate.insert( this.typeId, this.typeName, this.revTypeId, this.revTypeName);
			postlinkForm.typeDiv.appendChild( this.body );
		}

		// Get the elements we'll need to append/bind to.
		this.rowContainer = this.body.getElementsByClassName( 'postlink-rows' )[0];
		this.linkNameInput = this.body.getElementsByClassName( 'link-name' )[0];
		this.linkIdInput = this.body.getElementsByClassName( 'link-id' )[0];
	},

	destroy: function( target ) {
		if ( confirm( 'Are you sure you want to delete this connection type?' ) ) {
			var type = target.parentNode.parentNode,
				combinedId = this.typeId + '/' + this.revTypeId;

			var links = type.getElementsByClassName( 'postlink-connection' );
			for ( var i = 0, len = links.length; i < len; i++ ) {
				this.deleteLink( links[0] ); // Using 0 instead of i is weird, but it deals with the fact we're deleting from the node list as we go.
			}

			remove( type );
			events.publish( 'removeType', combinedId );
		}
	},

	alert: function() {
		var that = this;
		this.body.classList.add( 'pulse' );
		setTimeout( function() {
			that.body.classList.remove( 'pulse' );
		}, 700 );
	}
};

var typeTemplate = (function( $ ) {
	var template,
		addLinkButton,
		deleteTypeButton;

	function init() {
		template = document.getElementById( 'type-template' );
		template = remove( template );
		template.classList.remove( 'template' );
		template.removeAttribute( 'id' );
		template.heading = template.getElementsByTagName( 'h3' )[0];
	}

	function insert( typeId, typeName, revTypeId, revTypeName ) {
		if ( 0 !== typeId ) {
			var title = typeName;
		}
		if ( 0 !== revTypeId ) {
			title += '<span class="rev-link"> / ' + revTypeName;
		}
		template.heading.innerHTML = title;
		return template.cloneNode( true );
	}

	init();

	return {
		insert: insert
	};
})();

var rowTemplate = (function() {
	var template,
		idField,
		form;

	function init() {
		template = document.getElementById( 'row-template' );
		template = remove( template );
		template.classList.remove( 'template' );
		template.label = template.getElementsByClassName( 'postlink-label' )[0];
		idField = template.getElementsByClassName( 'link-id-input' )[0];
	}

	function insert( linkName, linkId ) {
		template.label.innerHTML = linkName;
		idField.value = linkId;
		return template.cloneNode( true );
	}

	init();

	return {
		insert: insert
	};
})();

/**
 * A simple Publish/subscribe javascript module based on code by David Walsh:
 * http://davidwalsh.name/pubsub-javascript
 */
var events = (function(){
	// Holds the subscribed topics.
	var topics = {};

	return {
		subscribe: function( topic, callback ) {
			// Create the topic's object if not yet created.
			if( ! topics[topic] ) {
				topics[topic] = { subscribers: [] };
			}

			// Add the callback to subscribers list.
			var index = topics[topic].subscribers.push( callback ) - 1;

			// Remove subscriber from a topic.
			return {
				remove: function() {
					delete topics[topic].subscribers[index];
				}
			};
		},

		publish: function(topic, info) {
			// Return early if the topic doesn't exist, or there are no subscribers.
			if( ! topics[topic] || ! topics[topic].subscribers.length ) {
				return;
			}

			// Cycle through topics subscribers, fire!
			var items = topics[topic].subscribers;

			for ( var i = 0, len = items.length; i < len; i++ ) {
				items[i]( info || {} );
			}
		}
	};
})();

postlinkForm.init();
