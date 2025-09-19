<?php function funeral_acf_admin_head() { ?>
	<style type="text/css">
		
		 body#tinymce,
		 body#tinymce p {
			 font-family: Verdana, Arial;
			 font-size: 1em;
		 }

		.inside.acf-fields>.acf-field:first-child {
			margin-bottom: 25px;
		}

		.inside.acf-fields>.acf-field {
			background-color: #F5F9FF;
			margin: 25px 0;
		}

		.acf-label {
			color: #25477b;
		}

		.acf-label .description{
			color: #000;
		}

		.acf-field-object .acf-label {
			color: #000;
		}

		#acf-field-group-locations .acf-label,
		#acf-field-group-options .acf-label {
			color:  #000;
			background-color: transparent;
		}
		
	
		body .acf-block-fields,
		.edit-post-sidebar {
			 /* font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; */
			 font-size: 13px;
			 line-height: 1.4em;
		}
		
		.acf-field input[type="text"],
		.acf-field input[type="password"],
		.acf-field input[type="number"],
		.acf-field input[type="search"],
		.acf-field input[type="email"],
		.acf-field input[type="url"],
		.acf-field textarea,
		.acf-field select {
			 font-size: 14px;
			 line-height: 1.4;
			 box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
			 transition: 50ms border-color ease-in-out;
			 border-radius: 0;
			 border: 1px solid #ddd;
			 color: #32373c;
		}
		
		.acf-field textarea.wp-editor-area {
			 box-shadow: none;
			 border: none;
		}
		
		.acf-field input[type="checkbox"],
		.acf-field input[type="radio"] {
			 border: 1px solid #b4b9be;
			 color: #555;
			 height: 16px;
			 width: 16px;
			 border-radius: 0;
		}
		
		.acf-field input[type="radio"] {
			 border-radius: 50%;
			 padding: 1px !important;
		}
		
		.acf-field input[type="radio"]:checked::before {
		  content: "";
		  border-radius: 50%;
		  width: .7rem !important;
		  height: .7rem !important;
		  margin: .04rem !important;
		  background-color: #3582c4 !important;
		  line-height: 1.14285714;
		}
		
		.acf-field-643f22b4145e1 .acf-label {
		  margin-bottom: 0;
		  padding-bottom: 0;
		}
		
		.acfe-image-selector label .image.svg {
			background-size: contain !important;
			 }
		  .acfe-image-selector > ul > li > label > .image.svg {
		   background-size: contain !important;
		  }
		  .acf-field.acf-field-checkbox.acf-field-63c4d1126ab69.acfe-no-label {
			padding-top: 15px !important;
		  }

		  .acf-field.acf-field-message.acf-field-63263173fd141.acfe-no-label.-r0 {
			padding-top: 0px;
		  }
		  
		  .no-days .acf-input {
			width: 15% !important;
		  }
		  
		  
		  element {
		  
		  }
		  .acf-field-6459d56ac9a67 #set-post-thumbnail {
			  display: inline-block;
			  text-decoration: none;
			  font-size: 13px;
			  line-height: 2.15384615;
			  min-height: 30px;
			  margin: 0;
			  padding: 0 10px;
			  cursor: pointer;
			  border-width: 1px;
			  border-style: solid;
			  -webkit-appearance: none;
			  border-radius: 3px;
			  white-space: nowrap;
			  box-sizing: border-box;
			  border-color: #7e8993;
			  color: #32373c;
			  background: #f6f7f7;  
		  }
		  
		
	</style>

<?php }

add_action('acf/input/admin_head', 'funeral_acf_admin_head');