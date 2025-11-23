(function () {
    "use strict"

    // Wait for DOM to be ready and all libraries to be loaded
    function initProjectForm() {
        console.log('PuzzlingCRM: initProjectForm called');
        console.log('PuzzlingCRM: Quill available?', typeof Quill !== 'undefined');
        console.log('PuzzlingCRM: Choices available?', typeof Choices !== 'undefined');
        console.log('PuzzlingCRM: FilePond available?', typeof FilePond !== 'undefined');
        
        // Check if required libraries are loaded
        if (typeof Quill === 'undefined' || typeof Choices === 'undefined' || typeof FilePond === 'undefined') {
            // If libraries are not loaded yet, wait a bit and try again
            console.log('PuzzlingCRM: Libraries not ready, retrying...');
            setTimeout(initProjectForm, 100);
            return;
        }
        
        console.log('PuzzlingCRM: All libraries loaded, initializing form...');

        /* multi select with remove button */
        const assignedTeamMembersElement = document.querySelector('#assigned-team-members');
        if (assignedTeamMembersElement) {
            const multipleCancelButton = new Choices(
                '#assigned-team-members',
                {
                    allowHTML: true,
                    removeItemButton: true,
                }
            );
        }

        /* quill snow editor */
        var quillEditorElement = document.querySelector('#project-descriptioin-editor');
        console.log('PuzzlingCRM: Quill editor element found?', !!quillEditorElement);
        if (quillEditorElement) {
            console.log('PuzzlingCRM: Quill editor element content:', quillEditorElement.innerHTML.substring(0, 100));
            var toolbarOptions = [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'font': [] }],
                ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
                ['blockquote', 'code-block'],

                [{ 'header': 1 }, { 'header': 2 }],               // custom button values
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],      // superscript/subscript
                [{ 'indent': '-1' }, { 'indent': '+1' }],          // outdent/indent
                [{ 'direction': 'rtl' }],                         // text direction

                [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown

                [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
                [{ 'align': [] }],

                ['image', 'video'],
                ['clean']                                         // remove formatting button
            ];
            
            // Get existing content BEFORE initializing Quill (important!)
            var existingContent = quillEditorElement.innerHTML.trim();
            console.log('PuzzlingCRM: Existing content before init:', existingContent.substring(0, 100));
            console.log('PuzzlingCRM: Existing content length:', existingContent.length);
            
            // Initialize Quill - it will automatically read content from the div
            console.log('PuzzlingCRM: Initializing Quill editor...');
            var quill = new Quill('#project-descriptioin-editor', {
                modules: {
                    toolbar: toolbarOptions
                },
                theme: 'snow'
            });
            console.log('PuzzlingCRM: Quill editor initialized');
            
            // If Quill didn't pick up the content (empty editor), set it manually
            setTimeout(function() {
                var quillContent = quill.root.innerHTML.trim();
                console.log('PuzzlingCRM: Quill content after init:', quillContent.length);
                
                if ((!quillContent || quillContent === '<p><br></p>' || quillContent === '<p></p>') && 
                    existingContent && existingContent !== '<p><br></p>' && existingContent !== '' && existingContent !== '<p></p>') {
                    console.log('PuzzlingCRM: Setting content manually to Quill editor');
                    try {
                        // Use clipboard to properly convert HTML to Quill format
                        var delta = quill.clipboard.convert(existingContent);
                        quill.setContents(delta, 'silent');
                        console.log('PuzzlingCRM: Content set successfully');
                    } catch (e) {
                        console.error('PuzzlingCRM: Error setting content:', e);
                        // Fallback: direct HTML assignment
                        quill.root.innerHTML = existingContent;
                    }
                }
            }, 300);
        } else {
            console.log('PuzzlingCRM: Quill editor element not found!');
        }

        /* filepond */
        FilePond.registerPlugin(
            FilePondPluginImagePreview,
            FilePondPluginImageExifOrientation,
            FilePondPluginFileValidateSize,
            FilePondPluginFileEncode,
            FilePondPluginImageEdit,
            FilePondPluginFileValidateType,
            FilePondPluginImageCrop,
            FilePondPluginImageResize,
            FilePondPluginImageTransform
        );

        /* multiple upload */
        const MultipleElement = document.querySelector('.multiple-filepond');
        if (MultipleElement) {
            FilePond.create(MultipleElement);
        }

        /* passing unique values */
        const tagsInputElement = document.querySelector('#choices-text-unique-values');
        if (tagsInputElement) {
            var textUniqueVals = new Choices('#choices-text-unique-values', {
                allowHTML: true,
                paste: false,
                duplicateItemsAllowed: false,
                editItems: true,
            });
        }
        
        // Sync Quill editor content to hidden textarea before form submission
        var projectForm = document.querySelector('#pzl-project-form');
        if (projectForm) {
            projectForm.addEventListener('submit', function(e) {
                var quillEditor = document.querySelector('#project-descriptioin-editor');
                if (quillEditor && typeof Quill !== 'undefined') {
                    var quill = Quill.find(quillEditor);
                    if (quill) {
                        var content = quill.root.innerHTML;
                        var textarea = document.querySelector('#project_content');
                        if (textarea) {
                            textarea.value = content;
                        }
                    }
                }
                
                // Sync Choices tags to hidden input
                var tagsInput = document.querySelector('#choices-text-unique-values');
                if (tagsInput && typeof Choices !== 'undefined') {
                    var tagsChoices = Choices.getInstance(tagsInput);
                    if (tagsChoices) {
                        var tags = tagsChoices.getValue(true);
                        var hiddenTagsInput = document.querySelector('#project_tags');
                        if (hiddenTagsInput) {
                            hiddenTagsInput.value = tags.join('، ');
                        }
                    }
                }
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProjectForm);
    } else {
        // DOM is already ready
        initProjectForm();
    }

})();