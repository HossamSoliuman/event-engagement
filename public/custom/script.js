Dropzone.autoDiscover = false;
var myDropzone = new Dropzone("#file-dropzone", {
    url: "{{ url('/match-csv') }}",
    paramName: "file",
    maxFilesize: 200,
    acceptedFiles: ".csv",
    addRemoveLinks: true,
    dictDefaultMessage: "Drag & drop your CSV file here or click to browse",
    dictRemoveFile: "Remove file",
});
myDropzone.on("addedfile", function(file) {
    $("#file").prop("disabled", true);
});
myDropzone.on("removedfile", function(file) {
    $("#file").prop("disabled", false);
});