<?php
/**
 * Service providers for the addon.
 *
 * @since 1.9.0
 */

use Masteriyo\Addons\GroupCourses\Providers\GroupCoursesServiceProvider;

return array_unique(
	array(
		GroupCoursesServiceProvider::class,
	)
);
