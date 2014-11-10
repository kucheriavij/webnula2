/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
String.prototype.translit = (function () {
	var L = {
			'А': 'A', 'а': 'a', 'Б': 'B', 'б': 'b', 'В': 'V', 'в': 'v', 'Г': 'G', 'г': 'g',
			'Д': 'D', 'д': 'd', 'Е': 'E', 'е': 'e', 'Ё': 'Yo', 'ё': 'yo', 'Ж': 'Zh', 'ж': 'zh',
			'З': 'Z', 'з': 'z', 'И': 'I', 'и': 'i', 'Й': 'Y', 'й': 'y', 'К': 'K', 'к': 'k',
			'Л': 'L', 'л': 'l', 'М': 'M', 'м': 'm', 'Н': 'N', 'н': 'n', 'О': 'O', 'о': 'o',
			'П': 'P', 'п': 'p', 'Р': 'R', 'р': 'r', 'С': 'S', 'с': 's', 'Т': 'T', 'т': 't',
			'У': 'U', 'у': 'u', 'Ф': 'F', 'ф': 'f', 'Х': 'Kh', 'х': 'kh', 'Ц': 'Ts', 'ц': 'ts',
			'Ч': 'Ch', 'ч': 'ch', 'Ш': 'Sh', 'ш': 'sh', 'Щ': 'Sch', 'щ': 'sch', 'Ъ': '', 'ъ': '',
			'Ы': 'Y', 'ы': 'y', 'Ь': "", 'ь': "", 'Э': 'E', 'э': 'e', 'Ю': 'Yu', 'ю': 'yu',
			'Я': 'Ya', 'я': 'ya'
		},
		r = '',
		k;
	for (k in L) r += k;
	r = new RegExp('[' + r + ']', 'g');
	k = function (a) {
		return a in L ? L[a] : '';
	};
	return function () {
		return this.replace(r, k);
	};
})();


angular.module('cms.common', []).factory('$uploader', [function() {
	return {
		upload : function(options) {
			return new function() {
				if (!options.file || !options.url) {
					return false;
				}

				this.xhr = new XMLHttpRequest();
				this.reader = new FileReader();

				this.progress = 0;
				this.uploaded = false;
				this.successful = false;
				this.lastError = false;

				var self = this;
				var uploadCanceled = false;

				self.cancelUpload = function () {
					uploadCanceled = true;
					this.xhr.abort();
				}

				self.reader.onload = function () {
					self.xhr.upload.addEventListener("progress", function (e) {
						if (e.lengthComputable) {
							if (options.onprogress instanceof Function) {
								options.onprogress.call(self, e.loaded, e.total);
							}
						}
					}, false);

					self.xhr.upload.addEventListener("load", function () {
						self.progress = 100;
						self.uploaded = true;
					}, false);

					self.xhr.upload.addEventListener("error", function (e) {
						self.lastError = {
							code: 1,
							text: 'Error uploading on server'
						};
					}, false);

					self.xhr.onreadystatechange = function () {
						var callbackDefined = options.oncomplete instanceof Function;

						if (this.readyState == 4) {
							if (this.status == 200) {
								if (!self.uploaded) {
									if (callbackDefined) {
										options.oncomplete.call(self, false);
									}
								} else {
									self.successful = true;

									if (callbackDefined) {
										options.oncomplete.call(self, true, this.responseText, uploadCanceled);
									}
								}
							} else {
								self.lastError = {
									code: this.status,
									text: 'HTTP response code is not OK (' + this.status + ')'
								};

								if (callbackDefined) {
									options.oncomplete.call(self, false, null, uploadCanceled);
								}
							}
						}
					};

					self.xhr.open("POST", options.url);
					self.xhr.setRequestHeader("Cache-Control", "no-cache");
					self.xhr.setRequestHeader("Accept", "text/html");

					if (self.xhr.sendAsBinary) {
						var boundary = "xxxxxxxxx";

						var body = "--" + boundary + "\r\n";
						body += "Content-Disposition: form-data; name=\"attribute\"\r\n\r\n" + options.attribute + "\r\n";
						body += "Content-Disposition: form-data; name='" + (options.fieldName || 'file') + "'; filename='" + unescape(encodeURIComponent(options.file.name)) + "'\r\n";
						body += "Accept: application/json\r\n"
						body += "Content-Type: application/octet-stream\r\n\r\n";
						body += self.reader.result + "\r\n";
						body += "--" + boundary + "--";

						self.xhr.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + boundary);
						self.xhr.sendAsBinary(body);
					} else {
						var formData = new FormData();
						formData.append(options.fieldName || 'file', options.file);
						formData.append('attribute', options.attribute);
						self.xhr.send(formData);
					}
				};

				self.reader.readAsBinaryString(options.file);
			};
		}
	};
}]).filter('filesize', function() {
	return function(bytes, precision) {
		if (isNaN(parseFloat(bytes)) || !isFinite(bytes)) return '-';
		if (typeof precision === 'undefined') precision = 1;
		var units = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'],
			number = Math.floor(Math.log(bytes) / Math.log(1024));
		return (bytes / Math.pow(1024, Math.floor(number))).toFixed(precision) +  ' ' + units[number];
	}
});