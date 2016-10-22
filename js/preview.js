function open_preview(title_id, tag_id) // Text ID not required thanks to tinyMCE
{
	// This file is part of RetroCMS.
	//
	// RetroCMS is free software: you can redistribute it and/or modify
	// it under the terms of the GNU General Public License as published by
	// the Free Software Foundation, either version 3 of the License, or
	// (at your option) any later version.
	//
	// RetroCMS is distributed in the hope that it will be useful,
	// but WITHOUT ANY WARRANTY; without even the implied warranty of
	// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	// GNU General Public License for more details.
	//
	// You should have received a copy of the GNU General Public License
	// along with RetroCMS.  If not, see <http://www.gnu.org/licenses/>.
	// Copyright 2016 William McPherson

	// Open preview in new tab
	// Using POST so we don't run into URI issues
	var form = $('<form></form>');

    form.attr("method", "post");
    form.attr("action", "/manage/preview.php");

    parameters = { title: document.getElementById(title_id).value, tags: document.getElementById(tag_id).value, text: tinymce.activeEditor.getContent({format : 'raw'}) }

    $.each(parameters, function(key, value) {
        var field = $('<input></input>');

        field.attr("type", "hidden");
        field.attr("name", key);
        field.attr("value", value);

        form.append(field);
    });

    // The form needs to be a part of the document in
    // order for us to be able to submit it.
    $(document.body).append(form);
    form.submit();
}