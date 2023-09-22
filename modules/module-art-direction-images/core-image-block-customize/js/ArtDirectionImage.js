import { useState, useEffect } from '@wordpress/element';

export function ArtDirectionImage({ id }){

    const [ image, setImage ] = useState( false );

    /** Fetch precreated image tag */
    useEffect(() => {

        if ( image ) return;

        const fetchImage = async () => {
            const headers = { 'Content-Type': 'application/json' }
            const query = `
                query ArtDirectionImage($id: ID!, $idType: MediaItemIdType!) {
                    mediaItem(id: $id, idType: $idType) {
                        artDirectioned
                    }
                }
            `;
            const variables = { "id": id, "idType": "DATABASE_ID" };
            
            // WPGraphQL Plugin must be enabled
            const res = await fetch( "/graphql", {
                headers,
                method: 'POST',
                body: JSON.stringify({ query, variables }),
            });
        
            const json = await res.json();

            if ( json.errors ) {
                console.error(json.errors);
                throw new Error( 'Failed to fetch fluid image' );
            }
			
            setImage( json.data.mediaItem.artDirectioned );

          };

          fetchImage();
    }, []);


    if ( ! image ) return null;

    return (
        <div className="wp-pronotron-fluid-image" dangerouslySetInnerHTML={{ __html: image }} />
    )
}
