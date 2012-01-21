<?php if (!$this->mode || $this->mode==='view'): ?>

    <div class="adm-section-group">
		<div class="btns-set">    
        	<button class="btn st2 sz2 btn-edit" onclick="return tabAction('edit', this);"><span>Edit</span></button>
        </div>
        <ul class="form-list">
            <li>
                <h4 class="label">Attribute 1</h4>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis.
            </li>
            <li>
                <h4 class="label">Attribute 2</h4>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis. Aliquam ac nisl magna, sit amet vestibulum ipsum. Vestibulum ultrices justo sagittis ante interdum volutpat. Curabitur ullamcorper, neque pulvinar commodo gravida, augue tellus interdum nulla, a pulvinar leo nisi ac nisl. Nullam bibendum luctus sem, eget interdum leo blandit auctor. Integer ullamcorper tellus non justo ultrices tempor. Vivamus eu augue justo. Suspendisse ut neque nec neque ultrices aliquam dictum sed orci.
            </li>
            <li>
                <h4 class="label">Attribute 3</h4>
            </li>
        </ul>
    </div>

<?php elseif ($this->mode==='edit'): ?>


    <form method="#" action="#" class="adm-section-group">
        <fieldset>
            <div class="btns-set">
            	<button class="btn st3 sz2 btn-cancel" onclick="return tabAction('cancel', this);"><span>Cancel</span></button>
            	<button class="btn st1 sz2 btn-save" onclick="return tabAction('save', this);"><span>Save</span></button>
            </div>
            <ul class="form-list">
                <li>
                    <h4 class="label">Attribute 1</h4>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis.
                </li>
                <li>
                    <h4 class="label">Attribute 2</h4>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus vestibulum convallis varius. Donec et odio quis est blandit mattis. Aliquam ac nisl magna, sit amet vestibulum ipsum. Vestibulum ultrices justo sagittis ante interdum volutpat. Curabitur ullamcorper, neque pulvinar commodo gravida, augue tellus interdum nulla, a pulvinar leo nisi ac nisl. Nullam bibendum luctus sem, eget interdum leo blandit auctor. Integer ullamcorper tellus non justo ultrices tempor. Vivamus eu augue justo. Suspendisse ut neque nec neque ultrices aliquam dictum sed orci.
                </li>
                <li>
                    <h4 class="label">Attribute 3</h4>
                </li>
            </ul>
        </fieldset>
    </form>

<?php endif ?>