# CHANGELOG

<% if(version && (version.name || version.number)) { %>##<% if(version.name){%> <%= version.name %><% } %> <%= version.number %> <%= '\n' %><% } %>
<% _.forEach(sections, function(section){ 
  if(section.commitsCount > 0) { %>
### <%= section.title %>
<% _.forEach(section.commits, function(commit){ %>  - <%= printCommit(commit, true) %><% }) %>
<% _.forEach(section.components, function(component){ %>  - **<%= component.name %>**
<% _.forEach(component.commits, function(commit){ %>    - <%= printCommit(commit, true) %><% }) %>
<% }) %>
<% } %>
<% }) %>
