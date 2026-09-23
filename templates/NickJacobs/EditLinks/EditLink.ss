<div class="ss-edit-link ss-edit-link--$Type" data-ss-edit-link="$Type" data-record-id="$RecordID">
    <div class="ss-edit-link__trigger" title="$ClassShortName #$RecordID">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true" focusable="false"><path d="M256 0C114.62 0 0 114.62 0 256s114.62 256 256 256 256-114.62 256-256S397.38 0 256 0m-34.38 361.17a61.5 61.5 0 0 1-27.16 16.39c-20.09 5.75-40.2 11.47-60.22 17.46-5.4 1.62-10.08 1.44-14.24-2.52-4.41-4.2-4.75-9.16-3.07-14.79 6.26-21 11.89-42.17 18.69-63 2.32-7.08 6.23-14.4 11.39-19.66 41.47-42.3 127.44-128.3 127.44-128.3L345 237.4l.14.14s-84.04 83.95-123.52 123.63m164.72-164.71L359 223.81 288.21 153l27.35-27.35a33 33 0 0 1 46.65 0l24.13 24.13a33 33 0 0 1 0 46.68"/></svg>
    </div>
    <div class="ss-edit-link__info">
        <span class="ss-edit-link__class">$ClassShortName</span>
        <span class="ss-edit-link__id">$RecordID</span>
        <% if $Title %><span class="ss-edit-link__title">$Title.XML</span><% end_if %>
    </div>
    <% if $CanEdit %>
        <div class="ss-edit-link__link">
            <a href="$Link.ATT"<% if $NewTab %> target="_blank" rel="noopener"<% end_if %>><% if $Type == 'page' %>edit this page<% else %>edit this block<% end_if %></a>
        </div>
    <% end_if %>
</div>
