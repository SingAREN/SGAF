# SGAF Shibboleth 3.x Service Provider (SP) Installation Guide 

### <a name=top></a> Table of Contents
1. <a href="#1">Introduction</a>
1. <a href="#2">Download and installation</a>
1. <a href="#3">Federation membership</a>
1. <a href="#4">Configuration</a>
1. <a href="#5">Layout</a>
1. <a href="#6">Protect a resource </a>
1. <a href="#7">Finishing up</a>
1. <a href="#8">Testing </a>
1. <a href="#9">Startup</a>
1. <a href="#10">Acknowledgement</a>

## <a name="1">1.</a> Introduction 

There is a lot of documentation on how to install a Shibboleth SP, covering Shibboleth 2.x - notably:
- Understanding Shibboleth: how it all fits together: [https://wiki.shibboleth.net/confluence/display/SHIB2/FlowsAndConfig](https://wiki.shibboleth.net/confluence/display/SHIB2/FlowsAndConfig) (useful for terminology and understanding how the Shibboleth SP uses session cookies)
- Installation: [https://wiki.shibboleth.net/confluence/display/SHIB2/Installation](https://wiki.shibboleth.net/confluence/display/SHIB2/Installation) (for installing on Linux, Mac, Solaris, Windows or for Java servlets)
- [https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPConfigurationElements](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPConfigurationElements)

This page documents a simple sequence of steps to get a Shibboleth SP working in the Singapore Access Federation (SGAF) environment.

This documentation covers Shibboleth SP 3.0.x. It has been tested on CentOS 7 (i386 and x86_64), but should work on other RedHat-based systems as well.

## <a name="2">2.</a> Download and installation
### Prerequisites

Before starting to build and configure the Shibboleth Sevice Provider, assure that the following prerequisites are met:
- Install prerequisites (Apache with mod_ssl)
		
		# yum install httpd mod_ssl

Firewall settings:
- inbound traffic:
	- webserver: port 80 and/or 443 are used by any browser-user
- outbound traffic:
	- Shibboleth daemon (shibd): has to be able to connect to every remote IdP in the federation on port 8443 for attribute fetching

### Installation
- Install latest version via YUM

#### For Centos V6

	# wget http://download.opensuse.org/repositories/security://shibboleth/CentOS_CentOS-6/security:shibboleth.repo \
         -P /etc/yum.repos.d
	# yum install shibboleth

#### For Centos V7

	# wget http://download.opensuse.org/repositories/security:/shibboleth/CentOS_7/security:shibboleth.repo \
         -P /etc/yum.repos.d
	
	# yum install shibboleth

## <a name="3">3.</a> Federation membership
Installing a Shibboleth SP only becomes useful after registering the SP into a federation.
 
The registration process is rather self-explanatory. The key points are:
- Navigate to the Federation Registry of the appropriate federation and select "Create a Service Provider"

**Step 1**: Select your Organization and enter a Name and Description of your system - and a Service URL for accessing your system.

**Step 2:** For Production systems, enter as many Additional Details as available - this will allow AAF to properly advertise the service through their service catalogue.

**Step 3:** Select the Shibboleth version you have installed. If you are using an alternative SAML 2 implementation select the Advanced Registration option. Enter the base URL for your system in the form: https://sp.example.org.
The FederationRegistry will automatically create all of the SAML2 endpoints.

**NB: IT IS A REQUIREMENT IN THE PRODUCTION FEDERATION THAT ALL SERVICES CONFIGURE AND USE HTTPS CONNECTIONS ON THEIR WEBSERVER UNDERPINNED BY A COMMONLY KNOWN CA (Comodo, Symantec etc).** This is also the preferred method of operation in the test federation though this is not always practical.

**Step 4**: Paste in the back-channel certificate generated when installing the shibboleth RPM package, this is generally self-signed and does not need to be validated by a remote CA. The certificate should be located in `/etc/shibboleth/sp-cert.pem`. Note that the CN in the certificate must match the hostname the service is being registered under. If this is an alias and your system thinks of itself with a different hostname, you will need to generate a new certificate with the correct hostname: run the following, substituting the externally visible hostname for sp.example.org:

	$ cd /etc/shibboleth
	# ./keygen.sh -f -h sp.example.org -e https://sp.example.org/shibboleth

**Step 5**: Select the attributes Requested and mark which of them are Required. For each attribute requested, give a good explanation for why the attribute is requested. This information will later be displayed to users as justification for why the information is being released.

**Step 6**: If requesting any specific values for `eduPersonEntitlement`, enter them here.

**Step 7**: Select NameID formats to be used by your service. If planning to use SAML1 in addition to SAML2, select also `urn:mace:shibboleth:1.0:nameIdentifier` in addition to the already selected `urn:oasis:names:tc:SAML:2.0:nameid-format:transient`.

**Step 8**: Click Submit and wait for a confirmation email.
- It is important to click on the link in the confirmation email that comes later - that makes you an
administrator of this SP in the FederationRegistry.
- If intending to support SAML1, it is also important to manually add SAML1 endpoints. Open the SP description and under Endpoints -> Assertion Consuming Service, add:
	- `urn:oasis:names:tc:SAML:1.0:profiles:artifact-01` with a value of the form: `https://sp.example.org/Shibboleth.sso/SAML/Artifact`
	- `urn:oasis:names:tc:SAML:1.0:profiles:browser-post` with a value of the form: `https://sp.example.org/Shibboleth.sso/SAML/POST`
	
## <a name="4">4.</a> Configuration

Download SGAF metadata signing certificate.

### SGAF Production Federation

	# wget https://ds.sgaf.org.sg/distribution/metadata/updated_metadata_cert.pem \
         -O /etc/shibboleth/sgaf-metadata-cert.pem

Edit `/etc/shibboleth/shibboleth2.xml`:
- *Replace all instances of `sp.example.org` with your hostname*.
- In the `<Sessions>` element:
	- Make session handler use SSL: set `handlerSSL="true"`
	
**Recommended**: go even further and in the '''Sessions''' Sessions element, change the
`"handlerURL"` handlerURL from a relative one (`"/Shibboleth.sso"` to an absolute one - `handlerURL="https://sp.example.org/Shibboleth.sso"`. In the URL, use the hostname used in the endpoint URLs registered in the Federation Registry. This makes sure the server is always issuing correct endpoint URLs in outgoing requests, even when users refer to the server with alternative names. This is in particular important when there are multiple hostnames resolving to your server (such as one prefixed with "www." and one without).

- Optionally, customize in the <Errors> element the pages and settings. Your users will come in contact with these if an error occurs. Change the `SupportContact` attribute to something more meaningful than `root@localhost`.
- Load the federation metadata: add the **following** (or equivalent) section into `/etc/shibboleth/shibboleth2.xml` just above the sample (commented-out)
`MetadataProvider` element.

##### SGAF Production Federation

    <MetadataProvider type="XML" url="https://ds.sgaf.org.sg/distribution/metadata/sgaf-metadata.xml"
        backingFilePath="metadata.sgaf.xml" reloadInterval="7200">
    <MetadataFilter type="RequireValidUntil" maxValidityInterval="2419200"/>
    <MetadataFilter type="Signature" certificate="sgaf-metadata-cert.pem"/>
    </MetadataProvider>

### Configure Session Initiator

Version 3.0
- Configure Session Initiator: locate the `<SSO>` element and:
	- Remove reference to default `idp.example.org` - delete the entityID attribute
	- Configure the Discovery Service URL in the `discoveryURL` attribute: `discoveryURL="https://ds.sgaf.org.sg/discovery/DS"`


- Attributes received from the IdP have to be mapped. This is configured in the attribute-map. Change the attribute mapping definition by either editing `attribute-map.xml` to accept attributes. Block those
attributes irrelevant for your SP as necessary.
- Attributes can be filtered out by Shibboleth. These filter rules are defined in `attributepolicy.xml`. However, the default setting is to just keep it as-is.
- Leave <CredentialResolver> to point to the default keypair.

#### 64-bit platforms

On x86_64, if you have installed also the i386 version of shibboleth and its configuration Apache configuration file is taking over, edit `/etc/httpd/conf.d/shib.conf` and change the path to the Shibboleth Apache module to the 64-bit
version:

	LoadModule mod_shib /usr/lib64/shibboleth/mod_shib_22.so

### Attribute-policy.xml
The `attribute-policy.xml` file describes rules to filter out attributes or let them pass.
More information on how to set up these rules is described at [https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAttributeFilter](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPAttributeFilter).

### Logging
There are 2 different Shibboleth-related log files you can access for troubleshooting.
- `native.log`: is located in `/var/log/httpd` and can be configured in `/etc/shibboleth/native.logger`
- `shibd.log`: is located in `/var/log/shibboleth` and can be configured in `/etc/shibboleth/shibd.logger`

Make sure that the right processes have write permissions to the log files!

## <a name="5">5.</a> Layout
Shibboleth ships with some default html pages. These can be found in the `/etc/shibboleth` folder. Modify these files to your own look and feel:

- accessError.html
- bindingTemplate.html
- globalLogout.html
- localLogout.html
- metadataError.html
- postTemplate.html
- sessionError.html
- sslError.html

## <a name="6">6.</a> Protect a resource
You can protect a resource with Shibboleth by configuring your Apache webserver. Edit the file `/etc/httpd/conf.d/shib.conf`:

		<Location /secure>
		 ShibRequestSetting authType shibboleth
		 ShibRequestSetting requireSession false
     		require valid-user
		</Location>

More information on how to protect your resource can be found
on [https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPhtaccess](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPhtaccess).
For an example on how to implement Dual Login with Shibboleth Lazy Sessions, see [KU Leuven's website](https://aai.kuleuven.be/shibboleth/examples/lazysessions/).

## <a name="7">7.</a> Finishing up
This should get you going.
- Startup Apache and shibd services:

#### CentOS 6:
	# service httpd start
	# service shibd start
	# chkconfig httpd on
	# chkconfig shibd on

#### CentOS 7:
	# systemctl start httpd
	# systemctl start shibd
	# systemctl enable httpd
	# systemctl enable shibd

## <a name="8">8.</a> Testing
- Place a script inside the protected directory (`/var/www/html/secure/info.php`). PHP example script such as the following is good enough:

		<?php print_r($_SERVER); ?>

- Access the protected directory/script (`http://your.server/secure/info.php`) from your
browser, this should trigger a complete SSO cycle where you can authenticate on your IdP
- Upon successful authentication, the page should display all received attributes. Make sure you have non-empty `Shib-Application-ID` amongst other attributes (if your IdP release them).
- Check your `shibd.log` to see if there are attributes received or errors encountered.

## <a name="9">9.</a> Startup
We recommend `shibd` start before `apache` start so that in case of `shibd` errors, apache isn't trying to make erroneous connections while something is being fixed.

----
## <a name="10">10.</a> Acknowledgement
This document is based off the [Australian Access Federation (AAF) technical documentation](http://wiki.aaf.edu.au/tech-info/).

We would like to thank them for the help they have provided with the Singapore Access Federation.
