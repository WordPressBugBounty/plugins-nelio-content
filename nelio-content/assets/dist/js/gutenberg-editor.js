(()=>{var e={4396:(e,t)=>{"use strict";function n(e){return e}t.L8=void 0,t.L8=function(){return n}}},t={};function n(r){var o=t[r];if(void 0!==o)return o.exports;var i=t[r]={exports:{}};return e[r](i,i.exports,n),i.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})};var r={};(()=>{"use strict";n.r(r),n.d(r,{initPage:()=>pe});const e=window.wp.data;function t(t,n){return(0,e.subscribe)(t,n)}function o(t,n){return(0,e.useSelect)(t,n)}const i=window.wp.editor,a=window.NelioContent.editPost,l=window.NelioContent.utils,s=window.wp.element,u=window.wp.apiFetch,c=n.n(u)(),d=window.lodash,f=window.NelioContent.data,g=window.NelioContent.socialMessageEditor,m=window.wp.domReady,p=n.n(m)();var v=(0,n(4396).L8)(),y=function(e){return!!e&&"function"==typeof e&&"ajaxPrefilter"in e},h=function(e){return!!e&&"object"==typeof e&&"data"in e},E=function(e){return!!e&&"object"==typeof e&&"action"in e};var w=function(){return w=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},w.apply(this,arguments)},b=function(e,t,n,r){return new(n||(n=Promise))((function(o,i){function a(e){try{s(r.next(e))}catch(e){i(e)}}function l(e){try{s(r.throw(e))}catch(e){i(e)}}function s(e){var t;e.done?o(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(a,l)}s((r=r.apply(e,t||[])).next())}))},P=function(e,t){var n,r,o,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},a=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return a.next=l(0),a.throw=l(1),a.return=l(2),"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function l(l){return function(s){return function(l){if(n)throw new TypeError("Generator is already executing.");for(;a&&(a=0,l[0]&&(i=0)),i;)try{if(n=1,r&&(o=2&l[0]?r.return:l[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,l[1])).done)return o;switch(r=0,o&&(l=[2&l[0],o.value]),l[0]){case 0:case 1:o=l;break;case 4:return i.label++,{value:l[1],done:!1};case 5:i.label++,r=l[1],l=[0];continue;case 7:l=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==l[0]&&2!==l[0])){i=0;continue}if(3===l[0]&&(!o||l[1]>o[0]&&l[1]<o[3])){i.label=l[1];break}if(6===l[0]&&i.label<o[1]){i.label=o[1],o=l;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(l);break}o[2]&&i.ops.pop(),i.trys.pop();continue}l=t.call(e,i)}catch(e){l=[6,e],r=0}finally{n=o=0}if(5&l[0])throw l[1];return{value:l[0]?l[1]:void 0,done:!0}}([l,s])}}};function x(n){return b(this,void 0,Promise,(function(){var r;return P(this,(function(o){switch(o.label){case 0:return[4,A(n.postId)];case 1:return r=o.sent(),[4,T(w(w({},r),{date:"none"!==r.date&&r.date,followers:n.attributes.followers,statistics:{engagement:{},pageviews:void 0}}))];case 2:return o.sent(),function(t,n){void 0===t&&(t=!1),void 0===n&&(n=!1);var r=(0,e.dispatch)(a.store),o=r.setEditorToClassic,i=r.setEditorToElementor;o(t),i(n)}(n.settings.isClassicEditor,n.settings.isElementorEditor),window.NelioContentTinyMCE={createMessage:S,getLinks:l.getLinks,isEmpty:l.isEmpty},i=n.settings.autoShareEndModes,(0,l.isEmpty)(i)||(0,(0,e.dispatch)(a.store).setAutoShareEndModes)(i),g=n.settings.shouldAuthorBeFollower,(0,(0,e.dispatch)(a.store).includeAuthorInFollowers)(g),function(t){var n=t.url,r=t.alt;n&&(0,(0,e.dispatch)(a.store).setExternalFeaturedImage)(n,r)}(n.attributes.externalFeatImage),function(t){var n=(0,e.dispatch)(a.store),r=n.receiveReferences,o=n.suggestReferences;r(t);var i=(0,d.filter)(t,{isSuggestion:!0});(0,l.isEmpty)(i)||o((0,d.map)(i,"url"))}(n.attributes.references),function(t){(0,(0,e.dispatch)(a.store).markQualityAnalysisAsFullyIntegrated)(!!t.isFullyIntegrated)}(c=n.settings.qualityAnalysis),function(t){if(!t.isYoastIntegrated){var n=(0,e.dispatch)(a.store).removeQualityCheckType;n("nelio-content/yoast-content"),n("nelio-content/yoast-seo")}}(c),function(t){var n=t.supportsFeatImage,r=t.canImageBeAutoSet;n?(0,(0,e.dispatch)(a.store).updateQualityCheckSettings)("nelio-content/featured-image",{canImageBeAutoSet:r}):(0,(0,e.dispatch)(a.store).removeQualityCheckType)("nelio-content/featured-image")}(c),(0,(0,e.select)(f.store).isMultiAuthor)()||(0,(0,e.dispatch)(a.store).removeQualityCheckType)("nelio-content/author"),function(){var t,n,r,o=(0,e.dispatch)(a.store);n=(t=o).setCustomField,r=t.removeCustomField,p((function(){var e=window.jQuery;y(e)&&e.ajaxPrefilter((function(e,t){if(h(e)&&"string"==typeof e.data&&h(t)&&E(t)){var o=new URLSearchParams(e.data);switch(t.action){case"add-meta":if(o.has("metavalue")){var i="#NONE#"===o.get("metakeyselect")?o.get("metakeyinput"):o.get("metakeyselect"),a=o.get("metavalue")||"";i&&n(v(i),a)}else{var l=Array.from(o.keys()).filter((function(e){return/meta\[\d+\]\[key\]/.test(e)})).map((function(e){return o.get(e)}))[0],s=Array.from(o.keys()).filter((function(e){return/meta\[\d+\]\[value\]/.test(e)})).map((function(e){return o.get(e)}))[0];l&&s&&n(v(l),s)}break;case"delete-meta":var u=o.get("id"),c=u?document.getElementById("meta-".concat(u,"-key")):null;(i=null==c?void 0:c.getAttribute("value"))&&r(v(i))}}}))}))}(),t((function(){var t=(0,e.select)(a.store),n=t.getDate,r=t.getStatus,o=n(),i=r();o===s&&i===u||(s=o,u=i,(0,(0,e.dispatch)(a.store).updateDatesInPostRelatedItems)())})),[2]}var i,s,u,c,g}))}))}function S(t,n){if(t=(0,d.trim)(t),!(0,l.isEmpty)(t)){var r=function(t){if((0,l.isEmpty)(t))return!1;var n=(0,e.select)(f.store).getSocialProfilesByNetwork;if((0,l.isEmpty)(n("twitter")))return!1;for(var r=(0,e.select)(a.store).getReferenceByUrl,o=0,i=t;o<i.length;o++){var s=r(i[o]);if(s&&s.twitter)return s.twitter}return!1}(n);r&&(t="".concat(t," /cc ").concat(r)),t+=" {permalink}";var o=(0,e.select)(a.store).getPost;(0,(0,e.dispatch)(g.store).openNewSocialMessageEditor)({text:t},{context:"post",post:o(),disabledProfileIds:(0,a.getDisabledProfiles)()})}}function A(e){return b(this,void 0,Promise,(function(){return P(this,(function(t){switch(t.label){case 0:return[4,c({path:"/nelio-content/v1/post/".concat(e,"?aws")})];case 1:return[2,t.sent()]}}))}))}function T(t){return b(this,void 0,void 0,(function(){var n,r;return P(this,(function(o){switch(o.label){case 0:return n=(0,e.dispatch)(a.store),r=n.loadPostItems,[4,(0,n.setPost)(t)];case 1:return o.sent(),r(),[2]}}))}))}const k=window.wp.plugins,I=window.NelioContent.taskEditor,C=window.NelioContent.components,O=window.wp.editPost;var _=function(t){var n=t.isQualityFullyIntegrated,r=(0,e.useDispatch)(O.store).openGeneralSidebar,o=(0,e.useDispatch)(a.store).togglePanel;return s.createElement(s.Fragment,null,!!n&&s.createElement(O.PluginPostStatusInfo,null,s.createElement(a.QualityAnalysisSummary,{onClick:function(){o("post-quality-analysis",!0),r("nelio-content/nelio-content-default-sidebar")}})),s.createElement(O.PluginPostStatusInfo,null,s.createElement(a.SocialMediaSummary,null)))};const F=window.wp.components,N=window.wp.i18n;var M=function(e,t,n){if(n||2===arguments.length)for(var r,o=0,i=t.length;o<i;o++)!r&&o in t||(r||(r=Array.prototype.slice.call(t,0,o)),r[o]=t[o]);return e.concat(r||Array.prototype.slice.call(t))},j=function(e){return o((function(t){var n=t(a.store).getPostType();return t(f.store).getPostTypes(e).some((function(e){return e.name===n}))}))},R=function(){var e=(0,a.usePanelToggling)("post-quality-analysis"),t=e[0],n=e[1];if(!j("quality-checks"))return null;var r=s.createElement(a.QualityAnalysisSummary,{label:(0,N._x)("Quality Analysis","text","nelio-content")});return s.createElement(F.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:r},s.createElement(a.QualityAnalysis,null))},B=function(){return s.createElement(O.PluginPrePublishPanel,null,s.createElement(R,null))},Q=function(){var e=(0,a.usePanelToggling)("post-analytics"),t=e[0],n=e[1],r=o((function(e){return e(a.store).getPost()})),i="publish"===(null==r?void 0:r.status),l=j("analytics");return r&&l&&i?s.createElement(F.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,N._x)("Analytics","text","nelio-content")},s.createElement(C.PostPageviewsAnalytics,{postId:r.id}),s.createElement(C.PostEngagementAnalytics,{postId:r.id})):null},D=function(){var e=(0,f.useIsSubscribed)(),t=(0,a.usePanelToggling)("comments"),n=t[0],r=t[1];return j("comments")?e?s.createElement(F.PanelBody,{initialOpen:n,opened:n,onToggle:r,title:(0,N._x)("Editorial Comments","text","nelio-content")},s.createElement(a.EditorialComments,null)):s.createElement(C.PremiumPlaceholderPanel,{title:(0,N._x)("Editorial Comments","text","nelio-content"),feature:"raw/editorial-comments"}):null},q=function(){var e=(0,f.useIsSubscribed)(),t=(0,a.usePanelToggling)("tasks"),n=t[0],r=t[1];return j("tasks")?e?s.createElement(F.PanelBody,{initialOpen:n,opened:n,onToggle:r,title:(0,N._x)("Editorial Tasks","text","nelio-content")},s.createElement(a.EditorialTasks,null)):s.createElement(C.PremiumPlaceholderPanel,{title:(0,N._x)("Editorial Tasks","text","nelio-content"),feature:"raw/editorial-tasks"}):null},U=function(){var e=(0,a.usePanelToggling)("external-featured-image"),t=e[0],n=e[1];return j("efi")?s.createElement(F.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,N._x)("External Featured Image","text","nelio-content")},s.createElement(a.ExternalFeaturedImage,null)):null},L=function(){var e=(0,a.usePanelToggling)("notifications"),t=e[0],n=e[1];return j("notifications")?s.createElement(F.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,N._x)("Notifications","text","nelio-content")},s.createElement(a.Notifications,null)):null},G=function(){var e=(0,a.usePanelToggling)("references"),t=e[0],n=e[1];return j("references")?s.createElement(F.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,N._x)("References","text","nelio-content")},s.createElement(a.References,null)):null},H=function(){var e=(0,a.usePanelToggling)("social-media"),t=e[0],n=e[1];return j("social")?s.createElement(F.PanelBody,{initialOpen:t,opened:t,onToggle:n,title:(0,N._x)("Social Media","text","nelio-content")},s.createElement(a.SocialMediaTools,null)):null},z=function(){var e=o((function(e){return e(f.store).getToday()}));return(0,l.hasSubscriptionPromo)(e)?s.createElement("div",{style:{padding:"1em",display:"flex"}},s.createElement("div",{style:{textAlign:"center",width:"100%"}},s.createElement(C.SubscribeAction,null))):null},J=function(){if(!o((function(e){var t=e(a.store).getPostType(),n=e(f.store).getPostTypes;return M(M(M(M(M(M(M(M([],n("comments"),!0),n("efi"),!0),n("future-actions"),!0),n("notifications"),!0),n("quality-checks"),!0),n("references"),!0),n("series"),!0),n("tasks"),!0).some((function(e){return e.name===t}))})))return null;var e=(0,C.getPremiumComponent)("gutenberg-editor/series-panel",(function(){return s.createElement(C.PremiumPlaceholderPanel,{title:(0,N._x)("Series","text","nelio-content"),feature:"raw/series"})})),t=(0,C.getPremiumComponent)("gutenberg-editor/future-actions-panel",(function(){return s.createElement(C.PremiumPlaceholderPanel,{title:(0,N._x)("Future Actions","text","nelio-content"),feature:"raw/future-actions"})}));return s.createElement(O.PluginSidebar,{name:"nelio-content-content-tools-sidebar",title:(0,N._x)("Nelio Content Tools","text","nelio-content")},s.createElement(z,null),s.createElement(R,null),s.createElement(Q,null),s.createElement(H,null),s.createElement(L,null),s.createElement(e,null),s.createElement(t,null),s.createElement(q,null),s.createElement(D,null),s.createElement(G,null),s.createElement(U,null))},Y=function(){return j("social")?s.createElement(O.PluginSidebar,{name:"nelio-content-social-sidebar",title:(0,N._x)("Nelio Content Social","text","nelio-content"),icon:"share"},s.createElement(a.SocialMediaSidebar,null)):null},K=function(e){var t=e.isQualityFullyIntegrated,n=(0,C.getPremiumComponent)("post-page/future-action-editor","null");return s.createElement(s.Fragment,null,s.createElement(_,{isQualityFullyIntegrated:t}),s.createElement(B,null),s.createElement(J,null),s.createElement(Y,null),s.createElement(a.ReferenceEditor,null),s.createElement(g.SocialMessageEditor,null),s.createElement(I.TaskEditor,null),s.createElement(n,null),s.createElement(C.PremiumDialog,null))};const V=window.React;var W;function X(){return X=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var r in n)({}).hasOwnProperty.call(n,r)&&(e[r]=n[r])}return e},X.apply(null,arguments)}const Z=function(e){return V.createElement("svg",X({xmlns:"http://www.w3.org/2000/svg",width:20,height:20,viewBox:"0 0 40 40"},e),W||(W=V.createElement("path",{fill:"inherit",d:"M20 2.5C10.335 2.5 2.5 10.335 2.5 20S10.335 37.5 20 37.5c5.532 0 10.457-2.574 13.664-6.582l-2.97-2.285C28.172 31.75 24.321 33.75 20 33.75c-7.594 0-13.75-6.156-13.75-13.75S12.406 6.25 20 6.25c3.146 0 6.036 1.069 8.352 2.848l-8.63 8.629-4.867-4.868-2.285 2.286 4.868 4.867 2.285 2.285 2.285-2.285 8.668-8.668 2.66-2.66q-.531-.623-1.121-1.188a18 18 0 0 0-1.201-1.06C28.005 3.986 24.182 2.5 20 2.5m14.428 9.598a1.886 1.886 0 0 0-1.885 1.884 1.886 1.886 0 0 0 1.885 1.887 1.886 1.886 0 0 0 1.886-1.887 1.886 1.886 0 0 0-1.886-1.884m1.187 6.017A1.886 1.886 0 0 0 33.73 20a1.886 1.886 0 0 0 1.886 1.885A1.886 1.886 0 0 0 37.5 20a1.886 1.886 0 0 0-1.885-1.885m-1.209 6.102a1.886 1.886 0 0 0-1.885 1.885 1.886 1.886 0 0 0 1.885 1.884 1.886 1.886 0 0 0 1.887-1.884 1.886 1.886 0 0 0-1.887-1.885"})))};var $=function(){return $=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},$.apply(this,arguments)};const ee=window.NelioContent.date;const te=window.wp.coreData;var ne=function(){return ne=Object.assign||function(e){for(var t,n=1,r=arguments.length;n<r;n++)for(var o in t=arguments[n])Object.prototype.hasOwnProperty.call(t,o)&&(e[o]=t[o]);return e},ne.apply(this,arguments)},re=function(e){return"number"==typeof e},oe=function(e){return!!e&&"object"==typeof e&&"id"in e&&"name"in e&&"slug"in e};function ie(){var n,r,o,s,u,c,g,m,p=(0,e.dispatch)(a.store);n=(0,e.select)(i.store).isEditedPostDirty,r=(0,e.select)(a.store).getPostId,o=(0,e.select)(f.store).getPost,s=(0,e.dispatch)(f.store),u=s.invalidateResolution,c=s.removePost,g=n(),m=r(),t((function(){var e=n();if(e!==g){var t=g;g=e,m&&t&&!e&&(c(m),u("getPost",[m]),o(m))}})),function(n){var r,o=n.setPostId,a=(0,e.select)(i.store),l=a.getEditedPostAttribute,s=a.isEditedPostNew;t((function(){if(!s()){var e=l("id");r!==e&&(r=e,o(e))}}),i.store)}(p),function(n){var r,o=n.setStatus,a=(0,e.select)(i.store).getEditedPostAttribute;t((function(){var e=a("status");r!==e&&(r=e,o(e))}),i.store)}(p),function(n){var r,o,s=n.setFeaturedImage,u=(0,e.select)(a.store).getExternalFeaturedImageUrl,c=(0,e.select)(i.store).getEditedPostAttribute,d=(0,e.select)(te.store).getEntityRecord;t((function(){var e=u();if(e){if(0===r&&o===e)return;return o=e||!1,void s(r=0,e)}var t=c("featured_media"),n=t?d("root","media",t):void 0,i=null==n?void 0:n.source_url,a=!!(0,l.isUrl)(i)&&i;r===t&&o===a||(r=t,o=a,s(t,a))}))}(p),function(n){var r,o=n.setTitle,a=(0,e.select)(i.store).getEditedPostAttribute,l=(0,d.debounce)(o,500);t((function(){var e=a("title");r!==e&&(r=e,l(e))}),i.store)}(p),function(n){var r,o=n.setDate,a=(0,e.select)(i.store),l=a.isEditedPostDateFloating,s=a.getEditedPostAttribute;t((function(){var e,t=function(e){if(e){var t=e.substring(0,10),n=e.substring(11,16);return(0,ee.wpifyDateTime)("c",t,n)}}(l()?"":null!==(e=s("date"))&&void 0!==e?e:"");r!==t&&(r=t,o(t))}),i.store)}(p),function(n){var r,o=n.setContent,a=(0,e.select)(i.store).getEditedPostAttribute,l=(0,d.debounce)(o,2500);t((function(){var e=a("content");r!==e&&(r=e,l(e))}),i.store)}(p),function(n){var r,o=n.setExcerpt,a=(0,e.select)(i.store).getEditedPostAttribute,l=(0,d.debounce)(o,500);t((function(){var e=a("excerpt");r!==e&&(r=e,l(e))}),i.store)}(p),function(n){var r,o=n.setPermalink,a=(0,e.select)(i.store).getPermalink,s=(0,d.debounce)(o,500);t((function(){var e,t=null!==(e=a())&&void 0!==e?e:"";r!==t&&(r=t,(0,l.isUrl)(t)&&s(t))}),i.store)}(p),function(n){var r,o,a=n.setAuthor,l=(0,e.select)(i.store).getEditedPostAttribute,s=(0,e.select)(f.store).getAuthor;t((function(){var e,t=l("author"),n=s(t),i=null!==(e=null==n?void 0:n.name)&&void 0!==e?e:"";r===t&&o===i||(r=t,o=i,a(t,i))}))}(p),function(n){var r=n.setTerms,o=(0,e.select)(a.store).getPostType,l=(0,e.select)(i.store).getEditedPostAttribute,s=void 0===l?function(){return[]}:l,u=(0,e.select)(te.store),c=u.getEntityRecord,f=u.getEntityRecords,g="";t((0,d.debounce)((function(){var e=f("root","taxonomy",{per_page:-1})||[],t=(0,d.filter)(e,(function(e){var t;return e.visibility.public&&e.types.includes(null!==(t=o())&&void 0!==t?t:"")})).map((function(e){return e.slug})),n=t.map((function(e){var t=function(e){switch(e){case"category":return"categories";case"post_tag":return"tags";default:return e}}(e),n=s(t);return(0,d.castArray)(n).filter(re).map((function(t){return c("taxonomy",e,t)})).filter(oe).map((function(e){return{id:e.id,name:e.name,slug:e.slug}}))})),i=JSON.stringify(n);i!==g&&(g=i,t.forEach((function(e,t){var o=n[t];o&&r(e,o)})))}),500))}(p),function(n){var r=n.setPost,o=(0,e.select)(a.store).getPost,l=(0,e.select)(i.store),s=l.isSavingPost,u=l.isAutosavingPost,c=l.isPreviewingPost,d=s(),f=u(),g=c();t((function(){var e=s(),t=u(),n=c(),i=d&&!e&&!f||f&&g&&!n;if(d=e,f=t,g=n,i){var a=o();A(a.id).then((function(e){return r(ne(ne({},a),{customFields:e.customFields,customPlaceholders:e.customPlaceholders}))}))}}),i.store)}(p)}const ae=window.wp.blockEditor,le=window.wp.richText;var se="nelio-content/highlight",ue="nelio-content/share";function ce(e){return!(0,l.isEmpty)(de(e))}function de(e){var t,n=(0,d.trim)(e.text.substring(null!==(t=e.start)&&void 0!==t?t:0,e.end));return(0,d.capitalize)(n[0])+n.substring(1)}function fe(e){var t,n,r=(0,d.find)(e,{type:"core/link"});return null!==(n=null===(t=null==r?void 0:r.attributes)||void 0===t?void 0:t.url)&&void 0!==n?n:""}var ge=function(e,t,n,r){return new(n||(n=Promise))((function(o,i){function a(e){try{s(r.next(e))}catch(e){i(e)}}function l(e){try{s(r.throw(e))}catch(e){i(e)}}function s(e){var t;e.done?o(e.value):(t=e.value,t instanceof n?t:new n((function(e){e(t)}))).then(a,l)}s((r=r.apply(e,t||[])).next())}))},me=function(e,t){var n,r,o,i={label:0,sent:function(){if(1&o[0])throw o[1];return o[1]},trys:[],ops:[]},a=Object.create(("function"==typeof Iterator?Iterator:Object).prototype);return a.next=l(0),a.throw=l(1),a.return=l(2),"function"==typeof Symbol&&(a[Symbol.iterator]=function(){return this}),a;function l(l){return function(s){return function(l){if(n)throw new TypeError("Generator is already executing.");for(;a&&(a=0,l[0]&&(i=0)),i;)try{if(n=1,r&&(o=2&l[0]?r.return:l[0]?r.throw||((o=r.return)&&o.call(r),0):r.next)&&!(o=o.call(r,l[1])).done)return o;switch(r=0,o&&(l=[2&l[0],o.value]),l[0]){case 0:case 1:o=l;break;case 4:return i.label++,{value:l[1],done:!1};case 5:i.label++,r=l[1],l=[0];continue;case 7:l=i.ops.pop(),i.trys.pop();continue;default:if(!((o=(o=i.trys).length>0&&o[o.length-1])||6!==l[0]&&2!==l[0])){i=0;continue}if(3===l[0]&&(!o||l[1]>o[0]&&l[1]<o[3])){i.label=l[1];break}if(6===l[0]&&i.label<o[1]){i.label=o[1],o=l;break}if(o&&i.label<o[2]){i.label=o[2],i.ops.push(l);break}o[2]&&i.ops.pop(),i.trys.pop();continue}l=t.call(e,i)}catch(e){l=[6,e],r=0}finally{n=o=0}if(5&l[0])throw l[1];return{value:l[0]?l[1]:void 0,done:!0}}([l,s])}}};function pe(n){return ge(this,void 0,Promise,(function(){return me(this,(function(r){switch(r.label){case 0:return u=function(t){var n=(0,(0,e.select)(i.store).getEditedPostAttribute)("nelio_content");n&&((0,l.isEquivalent)(t,n)||(0,(0,e.select)(i.store).getCurrentPost)().id&&(0,(0,e.dispatch)(i.store).editPost)({nelio_content:t},{undoIgnore:!0}))},c={},t((function(){var t=(0,e.select)(a.store),n=t.getAutoShareEndMode,r=t.getAutomationSources,o=t.getExternalFeaturedImageAlt,i=t.getExternalFeaturedImageUrl,l=t.getNetworkImageIds,s=t.getPost,f=t.getQueryArgs,g=t.getSeries,m=t.getSuggestedReferences,p=t.isAutoShareEnabled,v=s(),y=(0,a.processHtml)(v.content).highlights,h={autoShareEndMode:n(),automationSources:r(),efiAlt:o(),efiUrl:i()||"",followers:v.followers,highlights:y,isAutoShareEnabled:p(),networkImageIds:l(),permalinkQueryArgs:f(),series:g(),suggestedReferences:m()};(0,d.isEqual)(h,c)||u(h),c=h}),a.store),[4,x(n)];case 1:return r.sent(),o={isQualityFullyIntegrated:n.settings.qualityAnalysis.isFullyIntegrated},(0,k.registerPlugin)("nelio-content",{icon:s.createElement(Z,null),render:function(){return s.createElement(K,$({},o))}}),function(e,t){var n=document.getElementById(e);if(n){var r,o,i=n.querySelector(".inside");i&&(r=s.createElement(t,null),(o=i)&&(s.createRoot?(0,s.createRoot)(o).render(r):(0,s.render)(r,o)))}}("nelio-content-social-media",a.SocialMediaMetabox),ie(),[2]}var o,u,c}))}))}(0,le.registerFormatType)(se,{name:se,title:(0,N._x)("Social Media Highlight","text","nelio-content"),tagName:"ncshare",className:null,edit:function(e){var t=e.isActive,n=e.value,r=e.onChange;return s.createElement(ae.RichTextToolbarButton,{icon:s.createElement(Z,null),title:(0,N._x)("Social Media Highlight","text","nelio-content"),onClick:function(){return r((0,le.toggleFormat)(n,{type:se}))},isActive:t})},interactive:!1}),(0,le.registerFormatType)(ue,{name:ue,title:(0,N._x)("Share","command","nelio-content"),tagName:"span",className:"nc-share",edit:function(e){var t=e.value,n=o((function(e){return e(f.store).getSocialProfileIds()})),r=(0,a.useDisabledProfiles)().length<n.length;return s.createElement(ae.RichTextToolbarButton,{icon:"share",title:(0,N._x)("Share","command","nelio-content"),isDisabled:!ce(t)||!r,onClick:function(){return S(de(t),function(e){var t,n=null!==(t=(0,d.slice)(e.formats,e.start,e.end))&&void 0!==t?t:[];return(0,d.uniq)((0,d.filter)(n.map(fe))).filter(l.isUrl)}(t))}})},interactive:!1})})();var o=NelioContent="undefined"==typeof NelioContent?{}:NelioContent;for(var i in r)o[i]=r[i];r.__esModule&&Object.defineProperty(o,"__esModule",{value:!0})})();