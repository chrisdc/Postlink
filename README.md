# Postlink

Currently under development, this plugin enables many-to-many relationships between WordPress posts

## Including different post types.

By default you will only see the 'Link Posts' window when editing the standard post type. If you would like to show this for other post types then hook into the filter 'postlink_post_types' and return an array of the post types you would like to include.

### Example

```php
add_filter( 'postlink_post_types', 'yourprefix_link_post_types' );

function yourprefix_link_post_types( $post_types ) {
	$post_types = array( 'post', 'page, 'movie' );
	return $post_types
}
```
