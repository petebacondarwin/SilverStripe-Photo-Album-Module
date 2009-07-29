
	<% include Menu2 %>
			
	<div class="typography">


		<div id="contentLeft">
			$Content
		    $Form
		    $PageComments
	    </div>
		
		
		<div id="contentRight" >
			
			<div id="slideshow">
				<% control Photos %>
					<% if First %>
						<img id="{$ID}Image" class="active" <% control Photo %>src="$CroppedImage(380,440).URL" <% end_control %> title="$Caption" />
					<% else %>
						<img id="{$ID}Image" <% control Photo %>src="$CroppedImage(380,440).URL" <% end_control %>  title="$Caption" />
					<% end_if %>
				<% end_control %>
			</div>

		</div>
	
	</div>

	
