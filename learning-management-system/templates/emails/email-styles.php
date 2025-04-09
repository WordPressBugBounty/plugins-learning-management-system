<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/emails/email-styles.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates\Emails
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

?>

.email-template {
	max-width: 600px;
	border: 1px solid #EBECF2;
	border-radius: 4px;
	margin: 0 auto;
	margin-top: 32px;
	background: #F8F6FF;
}
.email-template .email-header{
	background-image: url("<?php echo esc_url( masteriyo_get_email_template_header_background_img() ); ?>");
	background-color: #EEEFFD;
	background-size: cover;
	object-fit: cover;
	/*width: 20px ;*/
	height: auto;
	text-align: left;
	background-repeat:no-repeat;
	padding: 60px 40px 35px 40px;
}
.email-template .email-header img{
	max-width:180px;
	height:auto;
}
.email-template .email-body{
	padding: 26px 40px;
}
.email-template .email-body h1{
	font-size: 2em;
	margin : 0.67em 0;
}
.email-template .email-body h2{
	font-size: 1.5em;
	margin: 0.75em 0;
}
.email-template .email-body h3{
	font-size: 1.17em;
	margin: 0.83em 0;
}
.email-template .email-body h4{
	font-size: 1em;
	margin: 1.12em 0;
}
.email-template .email-body h5{
	font-size: 0.83em;
	margin: 1.5em 0;
}
.email-template .email-body h6{
	font-size: 0.75em;
	margin: 1.67em 0;
}

.email-template .email-body p{
	font-size: 1em;
	margin: 1em 0;
}

.email-template .email-body button{
	padding: 12px 24px;
	border-radius: 4px;
	border: none;
	background-color: #4584FF;
}
.email-template .email-body button a{
	color: #fff;
	text-decoration: none;
	font-size: 18px;
	line-height: 26px;
}
.email-template .email-footer{
	border-top: 1px solid #EBECF2;
	padding: 16px 40px;
}
.email-template .email-footer h1{
	font-size: 2em;
	margin : 0.67em 0;
}
.email-template .email-footer h2{
	font-size: 1.5em;
	margin: 0.75em 0;
}
.email-template .email-footer h3{
	font-size: 1.17em;
	margin: 0.83em 0;
}
.email-template .email-footer h4{
	font-size: 1em;
	margin: 1.12em 0;
}
.email-template .email-footer h5{
	font-size: 0.83em;
	margin: 1.5em 0;
}
.email-template .email-footer h6{
	font-size: 0.75em;
	margin: 1.67em 0;
}

.email-template .email-footer p{
	font-size: 1em;
	margin: 1em 0;
}

@media (max-width:767px) {
	.email-template .email-footer p,
	.email-template .email-body p,
	.email-template .email-body button a{
		font-size: 16px;
		line-height: 24px;
	}
	.email-template .email-body button{
		padding: 8px 12px;
	}
	.email-template .email-body h2{
		font-size: 28px;
		line-height: 38px;
	}
}


.email-template--title {
	font-size: 20px;
	font-weight: 600;
	color: #07092F;
}

.email-template--button {
	border-radius: 4px;
	background-color: #78A6FF;
	padding:12px 16px;
	margin: 10px 0px;
	color: #fff;
	text-decoration: none;
	display: inline-block;
}

.email-text--bold {
	font-weight: 700;
}

svg {
	width: 12px;
}


//Order table.
.order_item td {
	vertical-align: middle;
	font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;
	word-wrap:break-word;
}

.order-list table {
	border-collapse: collapse;
}

.order-list thead th {
	background-color: #78A6FF;
	color: #fff
}

.order-list tbody tr:nth-child(even) {
	<!-- background-color: #EBECF2; -->
}

.order-list tr th,
.order-list tr td {
	border: 1px solid #EBECF2;
	padding: 8px;
}

.order-list {
	margin-bottom: 40px;
}

.order-list table {
	width: 100%;
	font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;
}

.order-list tfoot .masteriyo-price-amount{
	font-weight: 700;
}

.address{
	line-height: 1.5;
}
<?php
