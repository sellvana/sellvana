- $m = $this->model

%fieldset.adm-section-group
	%input(type="hidden" name="file" value="#{$m->id}")
	%ul.form-list
		%li
			%h4.label = $this->_('Source')
			.js-ace(name="source" style="width: 600px; height: 200px;") = $m->source
			
:javascript
	FCom.Admin.initAce()